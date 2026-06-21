<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Dto\MantisAttachment;
use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Helper\IssueIdParser;
use Artemeon\M2G\Service\MantisConnector;
use HelgeSverre\Toon\Toon;
use InvalidArgumentException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class IssueDetailsTool implements McpTool
{
    public const NAME = 'mantis-issue-details';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
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
}
