<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Mcp\Schema\ResourceDefinition;
use Mcp\Server\Handler\ResourceHandlerInterface;

/**
 * An MCP resource that bundles its {@see ResourceDefinition} with its handler.
 */
interface McpResource extends ResourceHandlerInterface
{
    public function getDefinition(): ResourceDefinition;
}
