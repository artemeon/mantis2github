<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use ahinkle\PackagistLatestVersion\PackagistLatestVersion;
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
        $name = self::getPackageName();

        $version = null;

        foreach (
            [
                __DIR__ . '/../../../../composer/installed.php',
                __DIR__ . '/../../../vendor/composer/installed.php',
                __DIR__ . '/../../vendor/composer/installed.php'
            ] as $file
        ) {
            if (file_exists($file)) {
                $installed = require $file;
                $version = $installed['versions'][$name]['pretty_version'] ?? null;

                break;
            }
        }

        unset($file, $installed);

        return $version;
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
