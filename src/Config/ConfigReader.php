<?php

declare(strict_types=1);

namespace Artemeon\M2G\Config;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    final public function read(): ?ConfigValues
    {
        $configFile = __DIR__ . '/../../../config.yaml';

        if (!file_exists($configFile)) {
            return null;
        }

        $content = file_get_contents($configFile);
        if (!$content) {
            throw new RuntimeException('Invalid config file provided.');
        }

        /**
         * @var array{
         *     MANTIS_URL?: string,
         *     MANTIS_TOKEN?: string,
         *     GITHUB_TOKEN?: string,
         *     GITHUB_REPOSITORY?: string,
         * } $config
         */
        $config = Yaml::parse($content);

        if (!isset($config['MANTIS_URL'], $config['MANTIS_TOKEN'], $config['GITHUB_TOKEN'], $config['GITHUB_REPOSITORY'])) {
            return null;
        }

        return new ConfigValues(
            $config['MANTIS_URL'],
            $config['MANTIS_TOKEN'],
            $config['GITHUB_TOKEN'],
            $config['GITHUB_REPOSITORY'],
        );
    }
}
