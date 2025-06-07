<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use Artemeon\M2G\Command\IssuesListCommand;

class HtmlTableConverter implements ConverterInterface
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
                $githubIssue = $githubIssue['issue' . $data['id']] ?? null;
                if (!$githubIssue) {
                    return '';
                }

                $url = $githubIssue['url'];
                $title = $githubIssue['title'];
                $number = $data['id'];
                $status = $githubIssue['closed'] ? 'closed' : 'open';
                $error = $status === 'closed' ? 'error' : '';

                return <<<HTML
<tr>
<td><a href="{$url}" target="_blank">{$number}</a></td>
<td>{$title}</td>
<td style="text-align: right;"><span class="label {$error}">{$status}</span></td>
</tr>
HTML;
            }, UpstreamIssueParser::parse($ticket->getUpstreamTicket()));

            $githubRows = implode(PHP_EOL, $githubIssues);

            $githubTable = '';
            if ($githubIssues !== []) {
                $githubTable = <<<HTML
<table style="width:100%;">
<thead>
<tr>
<th>ID</th>
<th>Title</th>
<th style="text-align: right;">Status</th>
</tr>
</thead>
<tbody>
{$githubRows}
</tbody>
</table>
HTML;
            }

            $issueUrl = $ticket->getIssueUrl();
            $id = $ticket->getId();
            $project = $ticket->getProject();
            $summary = $ticket->getSummary();
            $assignee = $ticket->getAssignee();
            $status = $ticket->getStatus();
            $rows[] = <<<HTML
<tr>
<td><a href="{$issueUrl}">{$id}</a></td>
<td>{$project}</td>
<td>{$summary}</td>
<td>{$assignee}</td>
<td>{$status}</td>
<td>{$githubTable}</td>
</tr>
HTML;
        }

        $headers = ['ID', 'Project', 'Summary', 'Assignee', 'Status', 'Upstream'];
        $htmlRows = implode(' ', $rows);
        $headersHTML = implode(PHP_EOL, array_map(static fn (string $header): string => '<th>' . $header . '</th>', $headers));
        $output = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/picnic">
<title>MANTIS/GitHub Issue Sync</title>
</head>
<body>
<table>
<thead>
<tr>
{$headersHTML}
</tr>
</thead>
<tbody>
{$htmlRows}
</tbody>
</table>
</body>
</html>
HTML;

        $cwd = getcwd();
        $date = date('Y-m-d-His');

        $outputPath = $cwd . '/mantis-ticketsync-' . $date . '.html';

        file_put_contents($outputPath, $output);

        $issuesListCommand->success('Table saved into ' . $outputPath);
    }
}
