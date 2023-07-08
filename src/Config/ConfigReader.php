<?php

declare(strict_types=1);

namespace Artemeon\M2G\Config;

use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    final public function read(): ?ConfigValues
    {
        $configFile = __DIR__ . '/../../../config.yaml';

        if (!file_exists($configFile)) {
            return null;
        }

        $config = Yaml::parse(file_get_contents($configFile));

        if (!$config['MANTIS_URL'] || !$config['MANTIS_TOKEN'] || !$config['GITHUB_TOKEN'] || !$config['GITHUB_REPOSITORY']) {
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
