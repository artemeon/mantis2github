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
     * }> $githubIssue
     */
    public static function convert(IssuesListCommand $issuesListCommand, array $tickets, array $githubIssue): void
    {
        $rows = [];

        foreach ($tickets as $ticket) {
            $githubIssues = array_map(static function (array $data) use ($githubIssue): string {
                $status = 'open';
                if ($githubIssue['issue' . $data['id']]['closed']) {
                    $status = 'closed';
                }

                return '#' . $data['id'] . ' (' . $status . ')';
            }, UpstreamIssueParser::parse($ticket->getUpstreamTicket()));

            $rows[] = [
                $ticket->getId(),
                $ticket->getProject(),
                $ticket->getSummary(),
                $ticket->getAssignee(),
                $ticket->getStatus(),
                implode(', ', $githubIssues),
            ];
        }

        $headers = ['ID', 'Project', 'Summary', 'Assignee', 'Status', 'Upstream'];
        $issuesListCommand->table($headers, $rows);
    }
}
