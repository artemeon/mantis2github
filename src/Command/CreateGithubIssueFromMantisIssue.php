<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Service\GithubConnector;
use Artemeon\M2G\Service\MantisConnector;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
            ->setDescription('Synchronize a Mantis issue to GitHub')
            ->addArgument('id', InputArgument::OPTIONAL, 'Mantis issue id');
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

        $mantisIssue = $this->askForMantisIssue();

        $this->info('');

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Summary', 'Resolution']);
        $table->addRow([$mantisIssue->getId(), $mantisIssue->getSummary(), $mantisIssue->getResolution()]);
        $table->render();

        if (!empty($mantisIssue->getUpstreamTicket())) {
            $this->info('');
            $this->warn("GitHub issue already exists");
            $this->info("\n {$mantisIssue->getUpstreamTicket()}\n");

            return 1;
        }

        $confirmation = $this->ask("\n Do you want to create a new issue on GitHub? [Y/n] ", 'n');

        if ($confirmation !== 'Y') {
            $this->error('Aborted.');

            return 1;
        }

        $this->info("\n Creating new GitHub issue ...");

        $newGithubIssue = GithubIssue::fromMantisIssue($mantisIssue);

        $labels = array_values(array_map(function ($label) {
            return $label['name'];
        }, array_filter($this->githubConnector->getLabels(), function ($label) use ($mantisIssue) {
            return strtolower($label['name']) === strtolower($mantisIssue->getProject());
        })));

        $newGithubIssue->setLabels($labels);

        try {
            $newGithubIssue = $this->githubConnector->createIssue($newGithubIssue);
        } catch (GuzzleException | \Exception $e) {
            $this->error('Failed to create GitHub issue.');

            return 1;
        }

        $this->success("\n Successfully created GitHub issue #{$newGithubIssue->getNumber()}.");

        $this->info("\n Updating upstream ticket of Mantis issue ...");

        $mantisIssue->setUpstreamTicket($newGithubIssue->getIssueUrl());
        $this->mantisConnector->patchUpstreamField($mantisIssue);

        $this->success("\n Mantis upstream issue updated successfully.");
        $this->success(" {$mantisIssue->getUpstreamTicket()}");

        if (empty($this->argument('id'))) {
            $startOver = $this->ask("\n Do you want to sync another issue? [Y/n] ", 'n');

            if ($startOver === 'Y') {
                $this->handle();
            }
        }

        return 0;
    }

    protected function askForMantisIssue(): MantisIssue
    {
        $id = $this->argument('id') ?? $this->ask("\n Mantis ID: ");

        if (!is_numeric($id)) {
            $this->error('Please provide a valid issue id.');

            if (empty($this->argument('id'))) {
                $this->askForMantisIssue();
            }

            exit(1);
        }

        $this->info("\n Fetching issue details ...");

        $mantisIssue = $this->mantisConnector->readIssue((int)$id);

        if ($mantisIssue === null) {
            $this->error('Issue not found.');

            if (empty($this->argument('id'))) {
                $this->askForMantisIssue();
            }

            exit(1);
        }

        return $mantisIssue;
    }
}
