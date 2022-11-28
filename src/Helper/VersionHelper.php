<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use ahinkle\PackagistLatestVersion\PackagistLatestVersion;
use Composer\InstalledVersions;
use Exception;

class VersionHelper
{
    public static function getPackageName(): ?string
    {
        $packageJson = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);

        return $packageJson['name'] ?? null;
    }

    public static function fetchVersion(): string
    {
        return InstalledVersions::getPrettyVersion('artemeon/mantis2github');
    }

    /**
     * @throws Exception
     */
    public static function latestVersion(): ?string
    {
        $packagist = new PackagistLatestVersion();

        return $packagist->getLatestRelease(self::getPackageName())['version'] ?? null;
    }

    /**
     * @throws Exception
     */
    public static function checkForUpdates(): bool
    {
        $currentVersion = self::fetchVersion();
        $latestVersion = self::latestVersion();

        if (!preg_match("/^[0-9]+\.[0-9]+\.[0-9]+$/", $currentVersion)) {
            return false;
        }

        return version_compare($currentVersion, $latestVersion, '<');
    }
}
