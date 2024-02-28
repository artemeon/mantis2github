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

    private MantisConnector $mantisConnector;
    private GithubConnector $githubConnector;
    private ?ConfigValues $config;

    public function __construct(MantisConnector $mantisConnector, GithubConnector $githubConnector, ?ConfigValues $config)
    {
        parent::__construct();

        $this->mantisConnector = $mantisConnector;
        $this->githubConnector = $githubConnector;
        $this->config = $config;
    }

    public function __invoke(): int
    {
        if (!$this->config) {
            return self::INVALID;
        }

        $mantisIssues = $this->mantisConnector->fetchIssues(410);

        $githubIssueIds = [];
        foreach ($mantisIssues as $issue) {
            $parsedIssues = array_map(static fn (array $data) => $data['id'], UpstreamIssueParser::parse($issue->getUpstreamTicket()));
            $githubIssueIds = [...$githubIssueIds, ...$parsedIssues];
        }

        $parts = [];
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

        $githubResult = $this->githubConnector->graphql($query)['data']['repository'];

        switch ($this->option('output')) {
            case 'html':
                HtmlTableConverter::convert($this, $mantisIssues, $githubResult);

                break;
            default:
                CliTableConverter::convert($this, $mantisIssues, $githubResult);
        }

        return self::SUCCESS;
    }
}
