<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Service\GithubConnector;
use Artemeon\M2G\Service\MantisConnector;
use JsonException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

class CreateGithubIssueFromMantisIssue extends Command
{
    protected string $signature = 'sync {ids* : Mantis issue IDs}';

    protected ?string $description = 'Synchronize a list of Mantis issues to GitHub';

    private GithubConnector $githubConnector;
    private MantisConnector $mantisConnector;

    public function __construct(MantisConnector $mantisConnector, GithubConnector $githubConnector)
    {
        parent::__construct();
        $this->mantisConnector = $mantisConnector;
        $this->githubConnector = $githubConnector;
    }

    /**
     * @throws JsonException
     */
    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('Mantis 2 GitHub Sync');

        $ids = array_unique($this->argument('ids'));
        $message = count($ids) !== 1 ? 'Creating issues ...' : 'Creating issue ...';

        $this->newLine();

        $issues = [];

        $this->spin(function () use ($ids) {
            $labels = array_map(static fn ($label) => $label['name'], $this->githubConnector->getLabels());

            foreach ($ids as $id) {
                $mantisIssue = $this->mantisConnector->readIssue((int)$id);

                if ($mantisIssue === null) {
                    $issues[] = [
                        'id' => $id,
                        'icon' => '<error>✕</error>',
                        'message' => '<error>Mantis issue not found.</error>',
                        'issue' => '',
                    ];
                    continue;
                }

                $newGithubIssue = GithubIssue::fromMantisIssue($mantisIssue);

                $filteredLabels = array_values(
                    array_filter($labels, static fn ($label) => strtolower($label) === strtolower($mantisIssue->getProject())),
                );

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

                $mantisIssue->setUpstreamTicket(
                    trim($mantisIssue->getUpstreamTicket() . ' ' . $newGithubIssue->getIssueUrl())
                );
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
            }
        }, $message);

        $this->newLine();

        $table = new Table($this->output);
        $table->setHeaders(['', 'Mantis issue ID', 'Message', 'GitHub Issue']);
        foreach ($issues as $issue) {
            $table->addRow([$issue['icon'], $issue['id'], $issue['message'], $issue['issue']]);
        }
        $table->render();

        $this->newLine();

        return self::SUCCESS;
    }
}
