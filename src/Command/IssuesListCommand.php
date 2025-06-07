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

    public function __construct(private readonly MantisConnector $mantisConnector, private readonly GithubConnector $githubConnector, private readonly ?ConfigValues $configValues)
    {
        parent::__construct();
    }

    public function __invoke(): int
    {
        if (!$this->configValues instanceof ConfigValues) {
            return self::INVALID;
        }

        $mantisIssues = $this->mantisConnector->fetchIssues(410);

        /** @var array<string> $githubIssueIds */
        $githubIssueIds = [];
        foreach ($mantisIssues as $mantiIssue) {
            $parsedIssues = array_map(static fn (array $data): int => $data['id'], UpstreamIssueParser::parse($mantiIssue->getUpstreamTicket()));
            $githubIssueIds = [...$githubIssueIds, ...$parsedIssues];
        }

        $parts = [];
        /** @var string $id */
        foreach (array_unique($githubIssueIds) as $id) {
            $parts[] = <<<GRAPHQL
issue{$id}: issue(number: {$id}) {
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

        $repo = $this->configValues->githubRepo;
        [$owner, $name] = explode('/', $repo);

        $query = <<<GRAPHQL
{
  repository(name: "{$name}", owner: "{$owner}") {
    {$issuesQuery}
  }
}

{$issueFragment}
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
