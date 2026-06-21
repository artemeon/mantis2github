<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Mcp\Schema\Tool;
use Mcp\Server\Handler\ToolHandlerInterface;

/**
 * An MCP tool that bundles its {@see Tool} definition with its handler.
 */
interface McpTool extends ToolHandlerInterface
{
    public function getDefinition(): Tool;
}
