<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use Artemeon\M2G\Command\IssuesListCommand;
use Artemeon\M2G\Dto\MantisIssue;

class CliTableConverter implements ConverterInterface
{
    /**
     * @param array<string, array{
     *     url: string,
     *     title: string,
     *     closed: bool,
     * }> $githubResult
     * @param array<MantisIssue> $mantisIssues
     */
    public static function convert(IssuesListCommand $command, array $mantisIssues, array $githubResult): void
    {
        $rows = [];

        foreach ($mantisIssues as $issue) {
            $githubIssues = array_map(static function (array $data) use ($githubResult) {
                $status = 'open';
                if ($githubResult['issue' . $data['id']]['closed']) {
                    $status = 'closed';
                }

                return '#' . $data['id'] . ' (' . $status . ')';
            }, UpstreamIssueParser::parse($issue->upstreamTicket));

            $rows[] = [
                $issue->id,
                $issue->project,
                $issue->summary,
                $issue->assignee,
                $issue->status,
                implode(', ', $githubIssues),
            ];
        }

        $headers = ['ID', 'Project', 'Summary', 'Assignee', 'Status', 'Upstream'];
        $command->table($headers, $rows);
    }
}
