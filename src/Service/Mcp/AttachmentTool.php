<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Dto\MantisAttachment;
use Artemeon\M2G\Service\MantisConnector;
use Mcp\Schema\Content\BlobResourceContents;
use Mcp\Schema\Content\Content;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

class AttachmentTool implements McpTool
{
    public const NAME = 'mantis-attachment';

    private const SIZE_LIMIT = 10 * 1024 * 1024;

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): Tool
    {
        return new Tool(
            name: self::NAME,
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
                (int) (self::SIZE_LIMIT / 1024 / 1024),
            ),
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

        if ($attachment->getSize() > self::SIZE_LIMIT) {
            return CallToolResult::error([
                new TextContent(sprintf(
                    'Attachment "%s" is %d bytes, which exceeds the %d MB limit.',
                    $attachment->getFilename(),
                    $attachment->getSize(),
                    (int) (self::SIZE_LIMIT / 1024 / 1024),
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
}
