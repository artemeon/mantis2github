<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Service\MantisConnector;

use function Termwind\{render, terminal};

class ReadMantisIssueCommand extends Command
{
    protected string $signature = 'read:mantis {id : The issue ID}';
    protected ?string $description = 'Read details of a Mantis issue';

    public function __construct(private MantisConnector $mantisConnector)
    {
        parent::__construct();
    }

    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('Mantis Issue Details');

        $issue = $this->fetchIssueDetails();

        terminal()->clear();

        if (in_array($issue->resolution, ['open', 'reopened'])) {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-green-500 text-white font-bold">
    Issue is {$issue->resolution}
</div>
HTML
            );
        } else {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-purple-500 text-white font-bold">
    Issue is {$issue->resolution}
</div>
HTML
            );
        }

        render(
            <<<HTML
<div class="mx-2 mb-1 font-bold">
    [{$issue->project}] {$issue->summary}
</div>
HTML
        );
        render(
            <<<HTML
<div class="mx-2 mb-1">
    {$issue->issueUrl}
</div>
HTML
        );

        if ($issue->upstreamTicket) {
            render(
                <<<HTML
<div class="mx-2 mb-1 font-bold">
    GitHub Issue URL:
</div>
HTML
            );
            render(
                <<<HTML
<div class="mx-2 mb-1">
    {$issue->upstreamTicket}
</div>
HTML
            );
        }

        if ($issue->assignee) {
            render(
                <<<HTML
<div class="mx-2 mb-1 font-bold">
    Assignee:
</div>
HTML
            );
            render(
                <<<HTML
<div class="mx-2 mb-1">
    {$issue->assignee}
</div>
HTML
            );
        }

        return self::SUCCESS;
    }

    private function fetchIssueDetails(): MantisIssue
    {
        $id = $this->argument('id');

        if (!is_numeric($id)) {
            $this->error('Please provide a valid issue id.');

            exit(1);
        }

        $this->info('Fetching issue details...');

        $issue = $this->mantisConnector->readIssue((int) $id);

        if ($issue === null) {
            $this->error('Issue not found.');

            exit(1);
        }

        return $issue;
    }
}
