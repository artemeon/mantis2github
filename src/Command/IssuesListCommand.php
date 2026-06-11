<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Helper\CliTableConverter;
use Artemeon\M2G\Helper\HtmlTableConverter;
use Artemeon\M2G\Helper\UpstreamIssueParser;
use Artemeon\M2G\Service\GithubConnector;
use Artemeon\M2G\Service\MantisConnector;

class IssuesListCommand extends Command
{
    protected string $signature = 'issues:list {--output= : Output Format}';
    protected ?string $description = 'Get a list of Mantis Tickets with their associated GitHub Issues.';

    public function __construct(private MantisConnector $mantisConnector, private GithubConnector $githubConnector, private ?ConfigValues $config)
    {
        parent::__construct();
    }

    public function __invoke(): int
    {
        if ($this->config === null) {
            return self::INVALID;
        }

        $mantisIssues = $this->mantisConnector->fetchIssues(410);

        /** @var array<string> $githubIssueIds */
        $githubIssueIds = [];
        foreach ($mantisIssues as $issue) {
            $parsedIssues = array_map(static fn (array $data) => $data['id'], UpstreamIssueParser::parse($issue->upstreamTicket));
            $githubIssueIds = [...$githubIssueIds, ...$parsedIssues];
        }

        $parts = [];
        /** @var string $id */
        foreach (array_unique($githubIssueIds) as $id) {
            $parts[] = <<<GRAPHQL
issue$id: issue(number: $id) {
  ...IssueFragment
}
GRAPHQL;
        }
        $issuesQuery = implode(PHP_EOL, $parts);

        $issueFragment = <<<GRAPHQL
fragment IssueFragment on Issue {
  title
  url
  closed
}
GRAPHQL;

        $repo = $this->config->getGithubRepo();
        [$owner, $name] = explode('/', $repo);

        $query = <<<GRAPHQL
{
  repository(name: "$name", owner: "$owner") {
    $issuesQuery
  }
}

$issueFragment
GRAPHQL;

        /** @var array{ repository: array<string, array{ title: string, url: string, closed: bool }> } $result */
        $result = $this->githubConnector->graphql($query)['data'];
        $githubResult = $result['repository'];

        match ($this->option('output')) {
            'html' => HtmlTableConverter::convert($this, $mantisIssues, $githubResult),
            default => CliTableConverter::convert($this, $mantisIssues, $githubResult),
        };

        return self::SUCCESS;
    }
}
