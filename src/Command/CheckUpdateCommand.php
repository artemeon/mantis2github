<?php

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Helper\VersionHelper;

use function Termwind\{render};

class CheckUpdateCommand extends Command
{
    protected function configure()
    {
        $this->setName('check_update')
            ->setDescription('Checks whether a new version is available');
    }

    protected function handle(): int
    {
        $currentVersion = VersionHelper::fetchVersion();
        $latestVersion = VersionHelper::latestVersion();
        $updateAvailable = VersionHelper::checkForUpdates();
        $name = VersionHelper::getPackageName();

        if ($updateAvailable) {
            render(<<<HTML
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
HTML);
        }

        return 0;
    }
}
