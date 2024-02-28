<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Helper\VersionHelper;
use Exception;
use JsonException;

use function Termwind\{render};

class CheckUpdateCommand extends Command
{
    protected string $signature = 'check_update';
    protected ?string $description = 'Checks whether a new version is available';
    protected bool $hidden = true;

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function __invoke(): int
    {
        $currentVersion = VersionHelper::fetchVersion();
        $latestVersion = VersionHelper::latestVersion();
        $updateAvailable = VersionHelper::checkForUpdates();
        $name = VersionHelper::getPackageName();

        if ($updateAvailable) {
            render(
                <<<HTML
<table>
    <thead>
        <tr>
            <th>Update available! $currentVersion -> $latestVersion</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><br>Please run:<br><br><code class="font-bold">composer global update $name</code><br></td>
        </tr>
    </tbody>
</table>
HTML
            );
        }

        return self::SUCCESS;
    }
}
