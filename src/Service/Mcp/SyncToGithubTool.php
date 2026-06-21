<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Dto\SyncResult;
use Artemeon\M2G\Dto\SyncStatus;
use Artemeon\M2G\Service\IssueSyncService;
use HelgeSverre\Toon\Toon;
use JsonException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class SyncToGithubTool implements McpTool
{
    public const NAME = 'mantis-sync-to-github';

    public function __construct(private readonly IssueSyncService $issueSyncService)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
            title: 'Sync Mantis Issues to GitHub',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'ids' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'integer',
                            'minimum' => 1,
                        ],
                        'minItems' => 1,
                        'description' => 'One or more numeric Mantis issue IDs to synchronize to GitHub.',
                    ],
                    'force' => [
                        'type' => 'boolean',
                        'description' => 'Re-sync issues that already have an upstream GitHub ticket. When false (default), already-synced issues are skipped.',
                        'default' => false,
                    ],
                ],
                'required' => ['ids'],
            ],
            description: 'Synchronize one or more Mantis issues to GitHub: creates a GitHub issue for each and writes its URL back to the Mantis "Upstream Ticket" field. Already-synced issues are skipped unless "force" is true.',
            annotations: new ToolAnnotations(
                readOnlyHint: false,
                destructiveHint: false,
                idempotentHint: false,
                openWorldHint: true,
            ),
        );
    }

    public function execute(array $arguments, ClientGateway $gateway): CallToolResult
    {
        $rawIds = $arguments['ids'] ?? null;
        if (!is_array($rawIds) || $rawIds === []) {
            return CallToolResult::error([
                new TextContent('"ids" must be a non-empty array of positive integer Mantis issue IDs.'),
            ]);
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (is_int($rawId) && $rawId > 0) {
                $ids[] = $rawId;
            } elseif (is_string($rawId) && ctype_digit($rawId) && (int) $rawId > 0) {
                $ids[] = (int) $rawId;
            } else {
                return CallToolResult::error([
                    new TextContent('"ids" must contain only positive integer Mantis issue IDs.'),
                ]);
            }
        }

        $force = ($arguments['force'] ?? false) === true;

        try {
            $results = $this->issueSyncService->sync($ids, $force);
        } catch (JsonException $e) {
            return CallToolResult::error([
                new TextContent('Sync failed while encoding a request: ' . $e->getMessage()),
            ]);
        }

        $payload = [
            'synced' => count(array_filter($results, static fn (SyncResult $r): bool => $r->status === SyncStatus::Synced)),
            'total' => count($results),
            'issues' => array_map(
                static fn (SyncResult $result): array => array_filter([
                    'mantis_id' => $result->mantisId,
                    'status' => $result->status->value,
                    'github_url' => $result->githubUrl,
                    'detail' => $result->detail,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
                $results,
            ),
        ];

        return CallToolResult::success([new TextContent(Toon::encode($payload))]);
    }
}
