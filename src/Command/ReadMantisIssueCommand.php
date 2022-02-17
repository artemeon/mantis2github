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

use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Service\MantisConnector;
use Symfony\Component\Console\Input\InputArgument;

use function Termwind\{render, terminal};

class ReadMantisIssueCommand extends Command
{
    private MantisConnector $mantisConnector;

    public function __construct(MantisConnector $mantisConnector)
    {
        parent::__construct();
        $this->mantisConnector = $mantisConnector;
    }

    protected function configure()
    {
        $this->setName('read:mantis')
            ->setDescription('Read details of a Mantis issue')
            ->addArgument('id', InputArgument::OPTIONAL, 'The issue id');
    }

    protected function header(): void
    {
        render(<<<HTML
<div class="my-1 mx-1 px-2 bg-green-500 text-gray-900 font-bold">
    Mantis Issue Details
</div>
HTML);
    }

    protected function handle(): int
    {
        $this->header();

        $issue = $this->askForIssue();

        terminal()->clear();

        if ($issue->getResolution() === 'open') {
            render(<<<HTML
<div class="my-1 mx-1 px-1 bg-green-500 text-gray-900">
    Issue is open
</div>
HTML);
        } else if ($issue->getResolution() === 'fixed') {
            render(
                <<<HTML
<div class="my-1 mx-1 px-1 bg-purple-500 text-gray-900">
    Issue is fixed
</div>
HTML
            );
        }

        render(<<<HTML
<div class="ml-1 font-bold">
    [{$issue->getProject()}] {$issue->getSummary()}
</div>
HTML);
        $this->info("\n {$issue->getIssueUrl()}");

        if ($issue->getUpstreamTicket()) {
            $this->info("\n GitHub issue URL:");
            $this->info(" {$issue->getUpstreamTicket()}");
        }

        if ($issue->getAssignee()) {
            $this->info("\n Assignee:");
            $this->info(" {$issue->getAssignee()}");
        }

        $this->info('');

        return 0;
    }

    protected function askForIssue(): ?MantisIssue
    {
        $id = $this->argument('id') ?? $this->ask(' Mantis Issue ID:');

        if (!is_numeric($id)) {
            $this->error('Please provide a valid issue id.');

            if (empty($this->argument('id'))) {
                $this->askForIssue();
            }

            exit(1);
        }

        $this->info("\n Fetching issue details...\n");

        $issue = $this->mantisConnector->readIssue((int)$id);

        if (!$issue) {
            $this->error('Issue not found.');

            if (empty($this->argument('id'))) {
                $this->askForIssue();
            }

            exit(1);
        }

        return $issue;
    }
}
