<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Mcp\Schema\ResourceDefinition;
use Mcp\Server\ClientGateway;

class MantisUrlResource implements McpResource
{
    public const NAME = 'mantis-url';

    public const URI = 'mantis://config/url';

    public function __construct(private readonly string $mantisUrl)
    {
    }

    public function getDefinition(): ResourceDefinition
    {
        return new ResourceDefinition(
            uri: self::URI,
            name: self::NAME,
            title: 'Configured Mantis URL',
            description: 'The base URL of the Mantis instance this server is configured against.',
            mimeType: 'text/plain',
        );
    }

    public function read(string $uri, ClientGateway $gateway): string
    {
        return $this->mantisUrl;
    }
}
