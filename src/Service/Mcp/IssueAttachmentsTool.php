<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Dto\MantisAttachment;
use Artemeon\M2G\Helper\IssueIdParser;
use Artemeon\M2G\Service\MantisConnector;
use HelgeSverre\Toon\Toon;
use InvalidArgumentException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class IssueAttachmentsTool implements McpTool
{
    public const NAME = 'mantis-issue-attachments';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
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
}
