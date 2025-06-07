<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\MantisTicket;
use Artemeon\M2G\Service\MantisConnector;

use function Termwind\{render, terminal};

class ReadMantisIssueCommand extends Command
{
    protected string $signature = 'read:mantis {id : The issue ID}';

    protected ?string $description = 'Read details of a Mantis issue';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
        parent::__construct();
    }

    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('Mantis Issue Details');

        $mantisIssue = $this->fetchIssueDetails();

        terminal()->clear();

        if (in_array($mantisIssue->getResolution(), ['open', 'reopened'])) {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-green-500 text-white font-bold">
    Issue is {$mantisIssue->getResolution()}
</div>
HTML
            );
        } else {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-purple-500 text-white font-bold">
    Issue is {$mantisIssue->getResolution()}
</div>
HTML
            );
        }

        render(
            <<<HTML
<div class="mx-2 mb-1 font-bold">
    [{$mantisIssue->getProject()}] {$mantisIssue->getSummary()}
</div>
HTML
        );
        render(
            <<<HTML
<div class="mx-2 mb-1">
    {$mantisIssue->getIssueUrl()}
</div>
HTML
        );

        if (!in_array($mantisIssue->getUpstreamTicket(), [null, '', '0'], true)) {
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
    {$mantisIssue->getUpstreamTicket()}
</div>
HTML
            );
        }

        if (!in_array($mantisIssue->getAssignee(), [null, '', '0'], true)) {
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
    {$mantisIssue->getAssignee()}
</div>
HTML
            );
        }

        return self::SUCCESS;
    }

    private function fetchIssueDetails(): MantisTicket
    {
        $id = $this->argument('id');

        if (!is_numeric($id)) {
            $this->error('Please provide a valid issue id.');

            exit(1);
        }

        $this->info('Fetching issue details...');

        $issue = $this->mantisConnector->readIssue((int) $id);

        if (!$issue instanceof MantisTicket) {
            $this->error('Issue not found.');

            exit(1);
        }

        return $issue;
    }
}
