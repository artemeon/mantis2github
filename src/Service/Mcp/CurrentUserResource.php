<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service\Mcp;

use Artemeon\M2G\Service\MantisConnector;
use HelgeSverre\Toon\Toon;
use Mcp\Schema\ResourceDefinition;
use Mcp\Server\ClientGateway;

class CurrentUserResource implements McpResource
{
    public const NAME = 'mantis-current-user';

    public const URI = 'mantis://users/me';

    public function __construct(private readonly MantisConnector $mantisConnector)
    {
    }

    public function getDefinition(): ResourceDefinition
    {
        return new ResourceDefinition(
            uri: self::URI,
            name: self::NAME,
            title: 'Current Mantis User',
            description: 'The Mantis user account that owns the configured API token (id, name, real name, email, access level).',
            mimeType: 'text/plain',
        );
    }

    public function read(string $uri, ClientGateway $gateway): string
    {
        $user = $this->mantisConnector->fetchCurrentUser();

        if ($user === null) {
            return Toon::encode(['error' => 'Unable to fetch the current Mantis user.']);
        }

        $payload = array_filter([
            'id' => $user->id,
            'name' => $user->name,
            'real_name' => $user->realName,
            'email' => $user->email,
            'access_level' => $user->accessLevel,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return Toon::encode($payload);
    }
}
