<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Service\GithubConnector;

use function Termwind\{render, style, terminal};

class ReadGithubIssueCommand extends Command
{
    protected string $signature = 'read:github {id : GitHub issue ID}';

    protected ?string $description = 'Read details of a GitHub issue';

    public function __construct(private readonly GithubConnector $githubConnector)
    {
        parent::__construct();
    }

    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('GitHub Issue Details');

        $githubIssue = $this->fetchIssueDetails();

        terminal()->clear();

        if ($githubIssue->getState() === 'open') {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-green-500 text-white font-bold">
    Issue is open
</div>
HTML
            );
        } elseif ($githubIssue->getState() === 'closed') {
            render(
                <<<HTML
<div class="my-1 mx-2 px-1 bg-purple-500 text-white font-bold">
    Issue is closed
</div>
HTML
            );
        }

        render(
            <<<HTML
<div class="mx-2 mb-1 font-bold">
    {$githubIssue->getTitle()}
</div>
HTML
        );
        render(
            <<<HTML
<div class="mx-2 mb-1">
    {$githubIssue->getIssueUrl()}
</div>
HTML
        );

        $assignees = array_map(
            static fn (
                array $assignee,
            ): string => sprintf('<a href="%s" class="px-1 bg-blue-500 text-black">%s</a>', $assignee['html_url'], $assignee['login']),
            $githubIssue->getAssignees(),
        );

        if ($assignees !== []) {
            $text = 'Assignee' . (count($assignees) > 1 ? 's' : '') . ':';
            render(
                <<<HTML
<div class="mx-2 mb-1">
    {$text}
</div>
HTML
            );
            $assigneesHtml = implode(' ', $assignees);
            render(
                <<<HTML
<div class="mx-2 mb-1">
    {$assigneesHtml}
</div>
HTML
            );
        }

        $labels = $githubIssue->getLabels();

        if ($labels !== []) {
            $labels = array_map(static function (array $label): string {
                style('label-' . $label['id'])->color('#' . $label['color']);

                return sprintf('<span class="px-1 bg-label-%s text-black">%s</span>', $label['id'], $label['name']);
            }, $labels);

            $text = 'Label' . (count($labels) > 1 ? 's' : '') . ':';
            render(
                <<<HTML
<div class="mx-2 mb-1">
    {$text}
</div>
HTML
            );

            $labelsHtml = implode(' ', $labels);

            render(
                <<<HTML
<div class="mx-2 mb-1">
    {$labelsHtml}
</div>
HTML
            );
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

        $issue = $this->githubConnector->readIssue((int) $id);

        if (!$issue instanceof GithubIssue) {
            $this->error('Issue not found.');

            if ($this->argument('id') === false || in_array($this->argument('id'), ['', '0'], true) || $this->argument('id') === [] || $this->argument('id') === null) {
                $this->fetchIssueDetails();
            }

            exit(self::FAILURE);
        }

        return $issue;
    }
}
