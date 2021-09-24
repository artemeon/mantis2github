<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Config;

use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    public function read(): ConfigValues
    {
        $configFile = __DIR__ . '/../../config.yaml';
        $config = Yaml::parse(file_get_contents($configFile));

        return new ConfigValues(
            $config['MANTIS_URL'],
            $config['MANTIS_TOKEN'],
            $config['GITHUB_TOKEN'],
            $config['GITHUB_REPOSITORY'],
        );

    }
}