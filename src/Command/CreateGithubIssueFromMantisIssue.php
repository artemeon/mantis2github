<?php

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Service\GithubConnector;
use Artemeon\M2G\Service\MantisConnector;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;

use function Termwind\render;

class CreateGithubIssueFromMantisIssue extends Command
{
    private GithubConnector $githubConnector;
    private MantisConnector $mantisConnector;

    public function __construct(MantisConnector $mantisConnector, GithubConnector $githubConnector)
    {
        parent::__construct();
        $this->mantisConnector = $mantisConnector;
        $this->githubConnector = $githubConnector;
    }

    protected function configure()
    {
        $this->setName('sync')
            ->setDescription('Synchronize a list of Mantis issues to GitHub')
            ->addArgument('ids', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Mantis issue ids');
    }

    protected function header(): void
    {
        render(
            <<<HTML
<div class="my-1 mx-1 px-2 bg-green-500 text-gray-900 font-bold">
    Mantis 2 GitHub Sync
</div>
HTML
        );
    }

    protected function handle(): int
    {
        $this->checkConfig();

        $this->header();

        $this->success(" Creates a new GitHub issue from a Mantis issue.");

        $ids = array_unique($this->argument('ids'));

        $this->info('');

        $progressBar = new ProgressBar($this->output, count($ids));
        $progressBar->start();

        $labels = array_map(function ($label) {
            return $label['name'];
        }, $this->githubConnector->getLabels());

        $issues = [];

        foreach ($ids as $id) {
            $mantisIssue = $this->mantisConnector->readIssue((int)$id);

            if (empty($mantisIssue)) {
                $issues[] = [
                    'id' => $id,
                    'icon' => '<error>✕</error>',
                    'message' => '<error>Mantis issue not found.</error>',
                    'issue' => '',
                ];
                continue;
            }

            $newGithubIssue = GithubIssue::fromMantisIssue($mantisIssue);

            $filteredLabels = array_values(array_filter($labels, function ($label) use ($mantisIssue) {
                return strtolower($label) === strtolower($mantisIssue->getProject());
            }));

            $newGithubIssue->setLabels($filteredLabels);
            $newGithubIssue = $this->githubConnector->createIssue($newGithubIssue);

            if ($newGithubIssue === null) {
                $issues[] = [
                    'id' => $id,
                    'icon' => '<error>✕</error>',
                    'message' => '<error>GitHub issue could not be created.</error>',
                    'issue' => '',
                ];
                continue;
            }

            $mantisIssue->setUpstreamTicket(trim($mantisIssue->getUpstreamTicket() . ' ' . $newGithubIssue->getIssueUrl()));
            $patched = $this->mantisConnector->patchUpstreamField($mantisIssue);

            if ($patched === false) {
                $issues[] = [
                    'id' => $id,
                    'icon' => '<error>✕</error>',
                    'message' => '<error>Upstream ticket URL could not be updated.</error>',
                    'issue' => '',
                ];
                continue;
            }

            $issues[] = [
                'id' => $id,
                'icon' => '<info>✓</info>',
                'message' => '<info>Mantis issue has been synchronized.</info>',
                'issue' => $newGithubIssue->getIssueUrl(),
            ];

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->info("\n");

        $table = new Table($this->output);
        $table->setHeaders(['', 'Mantis issue ID', 'Message', 'GitHub Issue']);
        foreach($issues as $issue) {
            $table->addRow([$issue['icon'], $issue['id'], $issue['message'], $issue['issue']]);
        }
        $table->render();

        $this->info('');

        return 0;
    }
}
