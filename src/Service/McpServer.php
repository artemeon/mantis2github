<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Service\Mcp\AttachmentTool;
use Artemeon\M2G\Service\Mcp\IssueAttachmentsTool;
use Artemeon\M2G\Service\Mcp\IssueDetailsTool;
use Artemeon\M2G\Service\Mcp\IssueNotesTool;
use Artemeon\M2G\Service\Mcp\MantisUrlResource;
use Artemeon\M2G\Service\Mcp\McpResource;
use Artemeon\M2G\Service\Mcp\McpTool;
use Artemeon\M2G\Service\Mcp\MyIssuesTool;
use Artemeon\M2G\Service\Mcp\SyncToGithubTool;
use Artemeon\M2G\Service\Mcp\UnassignedIssuesTool;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

class McpServer
{
    public function __construct(
        private readonly MantisConnector $mantisConnector,
        private readonly IssueSyncService $issueSyncService,
        private readonly string $mantisUrl,
        private readonly string $version,
    ) {
    }

    final public function run(): void
    {
        $builder = Server::builder()
            ->setServerInfo('mantis-mcp', $this->version);

        foreach ($this->tools() as $tool) {
            $builder->add($tool->getDefinition(), $tool);
        }

        foreach ($this->resources() as $resource) {
            $builder->add($resource->getDefinition(), $resource);
        }

        $builder->build()->run(new StdioTransport());
    }

    /**
     * @return list<McpTool>
     */
    private function tools(): array
    {
        return [
            new IssueDetailsTool($this->mantisConnector),
            new IssueAttachmentsTool($this->mantisConnector),
            new IssueNotesTool($this->mantisConnector),
            new MyIssuesTool($this->mantisConnector),
            new UnassignedIssuesTool($this->mantisConnector),
            new SyncToGithubTool($this->issueSyncService),
            new AttachmentTool($this->mantisConnector),
        ];
    }

    /**
     * @return list<McpResource>
     */
    private function resources(): array
    {
        return [
            new MantisUrlResource($this->mantisUrl),
        ];
    }
}
