<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Dto\MantisAttachment;
use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Dto\MantisNote;
use Artemeon\M2G\Dto\SyncResult;
use Artemeon\M2G\Dto\SyncStatus;
use Artemeon\M2G\Helper\IssueIdParser;
use HelgeSverre\Toon\Toon;
use InvalidArgumentException;
use JsonException;
use Mcp\Schema\Content\BlobResourceContents;
use Mcp\Schema\Content\Content;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\ClientGateway;
use Mcp\Server\Handler\ResourceHandlerInterface;
use Mcp\Server\Handler\ToolHandlerInterface;
use Mcp\Server\Transport\StdioTransport;

class McpServer
{
    private const ISSUE_DETAILS_TOOL = 'mantis-issue-details';

    private const ISSUE_ATTACHMENTS_TOOL = 'mantis-issue-attachments';

    private const ISSUE_NOTES_TOOL = 'mantis-issue-notes';

    private const MY_ISSUES_TOOL = 'mantis-my-issues';

    private const ASSIGNED_FILTER = 'assigned';

    private const SYNC_TOOL = 'mantis-sync-to-github';

    private const ATTACHMENT_TOOL = 'mantis-attachment';

    private const MANTIS_URL_RESOURCE = 'mantis-url';

    private const MANTIS_URL_RESOURCE_URI = 'mantis://config/url';

    private const ATTACHMENT_SIZE_LIMIT = 10 * 1024 * 1024;

    public function __construct(
        private readonly MantisConnector $mantisConnector,
        private readonly IssueSyncService $issueSyncService,
        private readonly string $mantisUrl,
        private readonly string $version,
    ) {
    }

    final public function run(): void
    {
        $server = Server::builder()
            ->setServerInfo('mantis-mcp', $this->version)
            ->add(...$this->issueDetailsTool())
            ->add(...$this->issueAttachmentsTool())
            ->add(...$this->issueNotesTool())
            ->add(...$this->myIssuesTool())
            ->add(...$this->syncToGithubTool())
            ->add(...$this->attachmentTool())
            ->add(...$this->mantisUrlResource())
            ->build();

        $server->run(new StdioTransport());
    }

    /**
     * @return array{Tool, ToolHandlerInterface}
     */
    private function issueDetailsTool(): array
    {
        $tool = new Tool(
            name: self::ISSUE_DETAILS_TOOL,
            title: 'Mantis Issue Details',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'The numeric Mantis issue ID.',
                        'minimum' => 1,
                    ],
                    'url' => [
                        'type' => 'string',
                        'description' => 'A Mantis issue URL (e.g. https://mantis.example.com/view.php?id=12345). The numeric id is extracted from the ?id= query parameter.',
                    ],
                ],
                'required' => [],
            ],
            description: 'Read details of a Mantis bug tracker ticket by ID or URL. Includes attachment metadata; use mantis-attachment to fetch a specific file.',
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            ),
        );

        $handler = new class ($this->mantisConnector) implements ToolHandlerInterface {
            public function __construct(private readonly MantisConnector $mantisConnector)
            {
            }

            public function execute(array $arguments, ClientGateway $gateway): CallToolResult
            {
                /** @var int|string|null $id */
                $id = $arguments['id'] ?? null;
                /** @var string|null $url */
                $url = $arguments['url'] ?? null;

                try {
                    $issueId = IssueIdParser::parse($id, $url);
                } catch (InvalidArgumentException $e) {
                    return CallToolResult::error([new TextContent($e->getMessage())]);
                }

                $issue = $this->mantisConnector->readIssue($issueId);
                if ($issue === null) {
                    return CallToolResult::error([
                        new TextContent(sprintf('Mantis issue %d not found or could not be fetched.', $issueId)),
                    ]);
                }

                return CallToolResult::success([new TextContent(Toon::encode(self::toPayload($issue)))]);
            }

            /**
             * @return array<string, mixed>
             */
            private static function toPayload(MantisIssue $issue): array
            {
                $payload = [
                    'id' => $issue->id,
                    'summary' => $issue->summary,
                    'description' => $issue->description,
                    'project' => $issue->project,
                    'status' => $issue->status,
                    'resolution' => $issue->resolution,
                    'assignee' => $issue->assignee,
                    'url' => $issue->issueUrl,
                    'upstream_ticket' => $issue->upstreamTicket,
                ];

                $filtered = array_filter(
                    $payload,
                    static fn (mixed $value): bool => $value !== null && $value !== '',
                );

                $attachments = array_map(
                    static fn (MantisAttachment $a): array => array_filter([
                        'id' => $a->getId(),
                        'filename' => $a->getFilename(),
                        'size' => $a->getSize(),
                        'content_type' => $a->getContentType(),
                    ], static fn (mixed $value): bool => $value !== null && $value !== ''),
                    $issue->attachments,
                );

                if ($attachments !== []) {
                    $filtered['attachments'] = $attachments;
                }

                return $filtered;
            }
        };

        return [$tool, $handler];
    }

    /**
     * @return array{Tool, ToolHandlerInterface}
     */
    private function issueAttachmentsTool(): array
    {
        $tool = new Tool(
            name: self::ISSUE_ATTACHMENTS_TOOL,
            title: 'List Mantis Issue Attachments',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'The numeric Mantis issue ID.',
                        'minimum' => 1,
                    ],
                    'url' => [
                        'type' => 'string',
                        'description' => 'A Mantis issue URL. The numeric id is extracted from the ?id= query parameter.',
                    ],
                ],
                'required' => [],
            ],
            description: 'List attachment metadata (id, filename, size, content type) for a Mantis ticket. Use mantis-attachment to fetch the bytes of a specific file.',
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            ),
        );

        $handler = new class ($this->mantisConnector) implements ToolHandlerInterface {
            public function __construct(private readonly MantisConnector $mantisConnector)
            {
            }

            public function execute(array $arguments, ClientGateway $gateway): CallToolResult
            {
                /** @var int|string|null $id */
                $id = $arguments['id'] ?? null;
                /** @var string|null $url */
                $url = $arguments['url'] ?? null;

                try {
                    $issueId = IssueIdParser::parse($id, $url);
                } catch (InvalidArgumentException $e) {
                    return CallToolResult::error([new TextContent($e->getMessage())]);
                }

                $attachments = $this->mantisConnector->listIssueFiles($issueId);
                if ($attachments === null) {
                    return CallToolResult::error([
                        new TextContent(sprintf('Could not fetch attachments for Mantis issue %d.', $issueId)),
                    ]);
                }

                $payload = [
                    'issue_id' => $issueId,
                    'attachments' => array_map(
                        static fn (MantisAttachment $a): array => array_filter([
                            'id' => $a->getId(),
                            'filename' => $a->getFilename(),
                            'size' => $a->getSize(),
                            'content_type' => $a->getContentType(),
                        ], static fn (mixed $value): bool => $value !== null && $value !== ''),
                        $attachments,
                    ),
                ];

                return CallToolResult::success([new TextContent(Toon::encode($payload))]);
            }
        };

        return [$tool, $handler];
    }

    /**
     * @return array{Tool, ToolHandlerInterface}
     */
    private function issueNotesTool(): array
    {
        $tool = new Tool(
            name: self::ISSUE_NOTES_TOOL,
            title: 'List Mantis Issue Notes',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'The numeric Mantis issue ID.',
                        'minimum' => 1,
                    ],
                    'url' => [
                        'type' => 'string',
                        'description' => 'A Mantis issue URL. The numeric id is extracted from the ?id= query parameter.',
                    ],
                ],
                'required' => [],
            ],
            description: 'List the notes (comments) of a Mantis ticket by ID or URL. Each note includes its reporter, text, creation time, and view state (public or private).',
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            ),
        );

        $handler = new class ($this->mantisConnector) implements ToolHandlerInterface {
            public function __construct(private readonly MantisConnector $mantisConnector)
            {
            }

            public function execute(array $arguments, ClientGateway $gateway): CallToolResult
            {
                /** @var int|string|null $id */
                $id = $arguments['id'] ?? null;
                /** @var string|null $url */
                $url = $arguments['url'] ?? null;

                try {
                    $issueId = IssueIdParser::parse($id, $url);
                } catch (InvalidArgumentException $e) {
                    return CallToolResult::error([new TextContent($e->getMessage())]);
                }

                $issue = $this->mantisConnector->readIssue($issueId);
                if ($issue === null) {
                    return CallToolResult::error([
                        new TextContent(sprintf('Mantis issue %d not found or could not be fetched.', $issueId)),
                    ]);
                }

                $payload = [
                    'issue_id' => $issueId,
                    'notes' => array_map(
                        static fn (MantisNote $n): array => array_filter([
                            'id' => $n->id,
                            'reporter' => $n->reporter,
                            'text' => $n->text,
                            'created_at' => $n->createdAt,
                            'view_state' => $n->viewState,
                        ], static fn (mixed $value): bool => $value !== null && $value !== ''),
                        $issue->notes,
                    ),
                ];

                return CallToolResult::success([new TextContent(Toon::encode($payload))]);
            }
        };

        return [$tool, $handler];
    }

    /**
     * @return array{Tool, ToolHandlerInterface}
     */
    private function myIssuesTool(): array
    {
        $tool = new Tool(
            name: self::MY_ISSUES_TOOL,
            title: 'List My Mantis Issues',
            // @phpstan-ignore argument.type (empty "properties" must serialize to a JSON object {}, not [])
            inputSchema: [
                'type' => 'object',
                'properties' => (object) [],
                'required' => [],
            ],
            description: 'List all Mantis tickets assigned to the current user (the owner of the configured API token). Returns a lean summary (id, summary, status, project, url) per ticket; use mantis-issue-details for the full ticket.',
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            ),
        );

        $assignedFilter = self::ASSIGNED_FILTER;
        $handler = new class ($this->mantisConnector, $assignedFilter) implements ToolHandlerInterface {
            public function __construct(
                private readonly MantisConnector $mantisConnector,
                private readonly string $assignedFilter,
            ) {
            }

            public function execute(array $arguments, ClientGateway $gateway): CallToolResult
            {
                $issues = $this->mantisConnector->fetchIssues($this->assignedFilter);

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
        };

        return [$tool, $handler];
    }

    /**
     * @return array{Tool, ToolHandlerInterface}
     */
    private function syncToGithubTool(): array
    {
        $tool = new Tool(
            name: self::SYNC_TOOL,
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

        $handler = new class ($this->issueSyncService) implements ToolHandlerInterface {
            public function __construct(private readonly IssueSyncService $issueSyncService)
            {
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
        };

        return [$tool, $handler];
    }

    /**
     * @return array{Tool, ToolHandlerInterface}
     */
    private function attachmentTool(): array
    {
        $tool = new Tool(
            name: self::ATTACHMENT_TOOL,
            title: 'Fetch Mantis Attachment',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'issue_id' => [
                        'type' => 'integer',
                        'description' => 'The Mantis issue ID that owns the attachment.',
                        'minimum' => 1,
                    ],
                    'file_id' => [
                        'type' => 'integer',
                        'description' => 'The attachment ID, as returned by mantis-issue-attachments or mantis-issue-details.',
                        'minimum' => 1,
                    ],
                ],
                'required' => ['issue_id', 'file_id'],
            ],
            description: sprintf(
                'Download a Mantis ticket attachment. Images come back as inline image content; text files as text; everything else as an embedded resource. Files larger than %d MB are rejected.',
                (int) (self::ATTACHMENT_SIZE_LIMIT / 1024 / 1024),
            ),
            annotations: new ToolAnnotations(
                readOnlyHint: true,
                destructiveHint: false,
                idempotentHint: true,
                openWorldHint: true,
            ),
        );

        $sizeLimit = self::ATTACHMENT_SIZE_LIMIT;
        $handler = new class ($this->mantisConnector, $sizeLimit) implements ToolHandlerInterface {
            public function __construct(
                private readonly MantisConnector $mantisConnector,
                private readonly int $sizeLimit,
            ) {
            }

            public function execute(array $arguments, ClientGateway $gateway): CallToolResult
            {
                $issueId = $this->positiveInt($arguments['issue_id'] ?? null);
                $fileId = $this->positiveInt($arguments['file_id'] ?? null);

                if ($issueId === null || $fileId === null) {
                    return CallToolResult::error([
                        new TextContent('Both "issue_id" and "file_id" must be provided as positive integers.'),
                    ]);
                }

                $attachment = $this->mantisConnector->fetchIssueFile($issueId, $fileId);
                if ($attachment === null) {
                    return CallToolResult::error([
                        new TextContent(sprintf('Attachment %d on Mantis issue %d not found or could not be fetched.', $fileId, $issueId)),
                    ]);
                }

                if ($attachment->getSize() > $this->sizeLimit) {
                    return CallToolResult::error([
                        new TextContent(sprintf(
                            'Attachment "%s" is %d bytes, which exceeds the %d MB limit.',
                            $attachment->getFilename(),
                            $attachment->getSize(),
                            (int) ($this->sizeLimit / 1024 / 1024),
                        )),
                    ]);
                }

                $base64 = $attachment->getContentBase64();
                if ($base64 === null) {
                    return CallToolResult::error([
                        new TextContent(sprintf('Attachment "%s" has no content available.', $attachment->getFilename())),
                    ]);
                }

                return CallToolResult::success([self::toContent($attachment, $base64, $issueId)]);
            }

            private function positiveInt(mixed $value): ?int
            {
                if (is_int($value) && $value > 0) {
                    return $value;
                }
                if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
                    return (int) $value;
                }

                return null;
            }

            private static function toContent(MantisAttachment $attachment, string $base64, int $issueId): Content
            {
                $mime = $attachment->getContentType() ?? 'application/octet-stream';
                $uri = sprintf('mantis://issues/%d/files/%d', $issueId, $attachment->getId());

                if (str_starts_with($mime, 'image/')) {
                    return new ImageContent($base64, $mime);
                }

                if (str_starts_with($mime, 'text/') || $mime === 'application/json' || $mime === 'application/xml') {
                    $decoded = base64_decode($base64, true);
                    if ($decoded !== false) {
                        return new EmbeddedResource(new TextResourceContents($uri, $mime, $decoded));
                    }
                }

                return new EmbeddedResource(new BlobResourceContents($uri, $mime, $base64));
            }
        };

        return [$tool, $handler];
    }

    /**
     * @return array{ResourceDefinition, ResourceHandlerInterface}
     */
    private function mantisUrlResource(): array
    {
        $resource = new ResourceDefinition(
            uri: self::MANTIS_URL_RESOURCE_URI,
            name: self::MANTIS_URL_RESOURCE,
            title: 'Configured Mantis URL',
            description: 'The base URL of the Mantis instance this server is configured against.',
            mimeType: 'text/plain',
        );

        $handler = new class ($this->mantisUrl) implements ResourceHandlerInterface {
            public function __construct(private readonly string $mantisUrl)
            {
            }

            public function read(string $uri, ClientGateway $gateway): string
            {
                return $this->mantisUrl;
            }
        };

        return [$resource, $handler];
    }
}
