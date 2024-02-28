<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use ahinkle\PackagistLatestVersion\PackagistLatestVersion;
use Composer\InstalledVersions;
use Exception;
use JsonException;

class VersionHelper
{
    /**
     * @throws JsonException
     */
    public static function getPackageName(): ?string
    {
        $path = __DIR__ . '/../../composer.json';
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (!$content) {
            return null;
        }

        $packageJson = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

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

        if (!preg_match("/^\d+\.\d+\.\d+$/", $currentVersion)) {
            return false;
        }

        return version_compare($currentVersion, $latestVersion, '<');
    }
}
