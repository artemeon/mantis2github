<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Service\GithubConnector;

use function Termwind\{render, style, terminal};

class ReadGithubIssueCommand extends Command
{
    protected string $signature = 'read:github {id : GitHub issue id}';
    protected ?string $description = 'Read details of a GitHub issue';

    private GithubConnector $githubConnector;

    /**
     * @param GithubConnector $mantisConnector
     */
    public function __construct(GithubConnector $mantisConnector)
    {
        parent::__construct();
        $this->githubConnector = $mantisConnector;
    }

    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('GitHub Issue Details');

        $issue = $this->fetchIssueDetails();

        terminal()->clear();

        if ($issue->getState() === 'open') {
            render(<<<HTML
<div class="my-1 mx-2 px-1 bg-green-500 text-white font-bold">
    Issue is open
</div>
HTML);
        } else if ($issue->getState() === 'closed') {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-purple-500 text-white font-bold">
    Issue is closed
</div>
HTML
            );
        }

        render(<<<HTML
<div class="mx-2 mb-1 font-bold">
    {$issue->getTitle()}
</div>
HTML);
        render(<<<HTML
<div class="mx-2 mb-1">
    {$issue->getIssueUrl()}
</div>
HTML);

        $assignees = array_map(static fn ($assignee) => "<a href=\"{$assignee['html_url']}\" class=\"px-1 bg-blue-500 text-black\">{$assignee['login']}</a>", $issue->getAssignees());

        if (count($assignees)) {
            $text = 'Assignee' . (count($assignees) > 1 ? 's' : '') . ':';
            render(<<<HTML
<div class="mx-2 mb-1">
    {$text}
</div>
HTML);
            $assigneesHtml = implode(' ', $assignees);
            render(<<<HTML
<div class="mx-2 mb-1">
    $assigneesHtml
</div>
HTML);
        }

        $labels = $issue->getLabels();

        if (count($labels)) {
            $labels = array_map(static function ($label) {
                style("label-{$label['id']}")->color('#' . $label['color']);
                return "<span class=\"px-1 bg-label-{$label['id']} text-black\">{$label['name']}</span>";
            }, $labels);

            $text = 'Label' . (count($labels) > 1 ? 's' : '') . ':';
            render(<<<HTML
<div class="mx-2 mb-1">
    {$text}
</div>
HTML);

            $labelsHtml = implode(' ', $labels);

            render(<<<HTML
<div class="mx-2 mb-1">
    $labelsHtml
</div>
HTML);
        }

        return self::SUCCESS;
    }

    private function fetchIssueDetails(): GithubIssue
    {
        $id = $this->argument('id');

        if (!is_numeric($id)) {
            $this->error('Please provide a valid issue id.');

            exit(self::INVALID);
        }

        $this->info('Fetching issue details...');

        $issue = $this->githubConnector->readIssue((int)$id);

        if (!$issue) {
            $this->error('Issue not found.');

            if (empty($this->argument('id'))) {
                $this->fetchIssueDetails();
            }

            exit(self::FAILURE);
        }

        return $issue;
    }
}
