<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Service\MantisConnector;
use HelgeSverre\Toon\Toon;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class UnassignedIssuesTool implements McpTool
{
    public const NAME = 'mantis-unassigned-issues';

    private const UNASSIGNED_FILTER = 'unassigned';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
            title: 'List Unassigned Mantis Issues',
            // @phpstan-ignore argument.type (empty "properties" must serialize to a JSON object {}, not [])
            inputSchema: [
                'type' => 'object',
                'properties' => (object) [],
                'required' => [],
            ],
            description: 'List Mantis tickets that are currently unassigned (no handler set). Returns a lean summary (id, summary, status, project, url) per ticket; use mantis-issue-details for the full ticket.',
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            ),
        );
    }

    public function execute(array $arguments, ClientGateway $gateway): CallToolResult
    {
        $issues = $this->mantisConnector->fetchIssues(self::UNASSIGNED_FILTER);

        $payload = [
            'issue_count' => count($issues),
            'issues' => array_map(
                static fn (MantisIssue $issue): array => array_filter([
                    'id' => $issue->id,
                    'summary' => $issue->summary,
                    'status' => $issue->status,
                    'project' => $issue->project,
                    'url' => $issue->issueUrl,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
                $issues,
            ),
        ];

        return CallToolResult::success([new TextContent(Toon::encode($payload))]);
    }
}
