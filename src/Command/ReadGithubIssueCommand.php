<?php

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Service\GithubConnector;
use Symfony\Component\Console\Input\InputArgument;

use function Termwind\{render, style, terminal};

class ReadGithubIssueCommand extends Command
{
    private GithubConnector $githubConnector;

    /**
     * @param GithubConnector $mantisConnector
     */
    public function __construct(GithubConnector $mantisConnector)
    {
        parent::__construct();
        $this->githubConnector = $mantisConnector;
    }

    protected function configure()
    {
        $this->setName('read:github')
            ->addArgument('id', InputArgument::REQUIRED, 'GitHub issue id')
            ->setDescription('Read details of a GitHub issue');
    }

    protected function header(): void
    {
        render(<<<HTML
<div class="my-1 mx-1 px-2 bg-green-500 text-gray-900 font-bold">
    GitHub Issue Details
</div>
HTML);
    }

    protected function handle(): int
    {
        $this->header();

        $issue = $this->fetchIssueDetails();

        terminal()->clear();

        if ($issue->getState() === 'open') {
            render(<<<HTML
<div class="my-1 mx-1 px-1 bg-green-500 text-gray-900">
    Issue is open
</div>
HTML);
        } else if ($issue->getState() === 'closed') {
            render(
                <<<HTML
<div class="my-1 mx-1 px-1 bg-purple-500 text-gray-900">
    Issue is closed
</div>
HTML
            );
        }

        render(<<<HTML
<div class="ml-1 font-bold">
    {$issue->getTitle()}
</div>
HTML);
        $this->info("\n {$issue->getIssueUrl()}\n");

        $assignees = array_map(function ($assignee) {
            return "<a href=\"{$assignee['html_url']}\" class=\"px-1 bg-blue-500 text-black\">{$assignee['login']}</a>";
        }, $issue->getAssignees());

        if (count($assignees)) {
            $this->info(' Assignee' . (count($assignees) > 1 ? 's' : '') . ':');
            $assigneesHtml = implode(' ', $assignees);
            render(<<<HTML
<div class="ml-1 my-1">
    $assigneesHtml
</div>
HTML);
        }

        $labels = $issue->getLabels();

        if (count($labels)) {
            $labels = array_map(function ($label) {
                style("label-{$label['id']}")->color('#' . $label['color']);
                return "<span class=\"px-1 bg-label-{$label['id']} text-black\">{$label['name']}</span>";
            }, $labels);

            $this->info(' Label' . (count($labels) > 1 ? 's' : '') . ':');

            $labelsHtml = implode(' ', $labels);

            render(<<<HTML
<div class="m-1">
    $labelsHtml
</div>
HTML);
        }

        return 0;
    }

    protected function fetchIssueDetails(): GithubIssue
    {
        $id = $this->argument('id');

        if (!is_numeric($id)) {
            $this->error('Please provide a valid issue id.');

            exit(1);
        }

        $this->info("\n Fetching issue details...\n");

        $issue = $this->githubConnector->readIssue((int)$id);

        if (!$issue) {
            $this->error('Issue not found.');

            if (empty($this->argument('id'))) {
                $this->fetchIssueDetails();
            }

            exit(1);
        }

        return $issue;
    }
}
