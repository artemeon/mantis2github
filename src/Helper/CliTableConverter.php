<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use Artemeon\M2G\Command\IssuesListCommand;

class CliTableConverter implements ConverterInterface
{
    /**
     * @param array<string, array{
     *     url: string,
     *     title: string,
     *     closed: bool,
     * }> $githubResult
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
            }, UpstreamIssueParser::parse($issue->getUpstreamTicket()));

            $rows[] = [
                $issue->getId(),
                $issue->getProject(),
                $issue->getSummary(),
                $issue->getAssignee(),
                $issue->getStatus(),
                implode(', ', $githubIssues),
            ];
        }

        $headers = ['ID', 'Project', 'Summary', 'Assignee', 'Status', 'Upstream'];
        $command->table($headers, $rows);
    }
}
