<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Dto\MantisNote;
use Artemeon\M2G\Helper\IssueIdParser;
use Artemeon\M2G\Service\MantisConnector;
use HelgeSverre\Toon\Toon;
use InvalidArgumentException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class IssueNotesTool implements McpTool
{
    public const NAME = 'mantis-issue-notes';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
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
}
