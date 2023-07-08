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

    private MantisConnector $mantisConnector;

    public function __construct(MantisConnector $mantisConnector)
    {
        parent::__construct();
        $this->mantisConnector = $mantisConnector;
    }

    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('Mantis Issue Details');

        $issue = $this->fetchIssueDetails();

        terminal()->clear();

        if (in_array($issue->getResolution(), ['open', 'reopened'])) {
            render(<<<HTML
<div class="my-1 mx-2 px-1 bg-green-500 text-white font-bold">
    Issue is {$issue->getResolution()}
</div>
HTML);
        } else {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-purple-500 text-white font-bold">
    Issue is {$issue->getResolution()}
</div>
HTML
            );
        }

        render(<<<HTML
<div class="mx-2 mb-1 font-bold">
    [{$issue->getProject()}] {$issue->getSummary()}
</div>
HTML);
        render(<<<HTML
<div class="mx-2 mb-1">
    {$issue->getIssueUrl()}
</div>
HTML);

        if ($issue->getUpstreamTicket()) {
            render(<<<HTML
<div class="mx-2 mb-1 font-bold">
    GitHub Issue URL:
</div>
HTML);
            render(<<<HTML
<div class="mx-2 mb-1">
    {$issue->getUpstreamTicket()}
</div>
HTML);
        }

        if ($issue->getAssignee()) {
            render(<<<HTML
<div class="mx-2 mb-1 font-bold">
    Assignee:
</div>
HTML);
            render(<<<HTML
<div class="mx-2 mb-1">
    {$issue->getAssignee()}
</div>
HTML);
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

        $issue = $this->mantisConnector->readIssue((int)$id);

        if (!$issue) {
            $this->error('Issue not found.');

            exit(1);
        }

        return $issue;
    }
}
