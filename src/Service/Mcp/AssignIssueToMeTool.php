<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Helper\IssueIdParser;
use Artemeon\M2G\Service\MantisConnector;
use HelgeSverre\Toon\Toon;
use InvalidArgumentException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class AssignIssueToMeTool implements McpTool
{
    public const NAME = 'mantis-assign-issue-to-me';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
            title: 'Assign Mantis Issue To Me',
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
            description: 'Assign a Mantis ticket (by ID or URL) to the current user (the owner of the configured API token).',
            annotations: new ToolAnnotations(
                readOnlyHint: false,
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

        $user = $this->mantisConnector->fetchCurrentUser();
        if ($user === null) {
            return CallToolResult::error([
                new TextContent('Unable to determine the current Mantis user.'),
            ]);
        }

        if (!$this->mantisConnector->assignIssue($issueId, $user->id)) {
            return CallToolResult::error([
                new TextContent(sprintf('Failed to assign Mantis issue %d to %s.', $issueId, $user->realName ?? $user->name)),
            ]);
        }

        $payload = [
            'issue_id' => $issueId,
            'assigned_to' => $user->realName ?? $user->name,
        ];

        return CallToolResult::success([new TextContent(Toon::encode($payload))]);
    }
}
