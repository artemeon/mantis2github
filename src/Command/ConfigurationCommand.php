<?php

namespace Artemeon\M2G\Command;

use function Termwind\{render};

class ConfigurationCommand extends Command
{
    protected string $configPath = __DIR__ . '/../../config.yaml';

    protected function configure()
    {
        $this->setName('configure');
        $this->setDescription('Configure the tool');
    }

    protected function handle(): int
    {
        $this->success("
  __  __                _    _        ____     ____  _  _    _   _         _     
 |  \/  |  __ _  _ __  | |_ (_) ___  |___ \   / ___|(_)| |_ | | | | _   _ | |__  
 | |\/| | / _` || '_ \ | __|| |/ __|   __) | | |  _ | || __|| |_| || | | || '_ \ 
 | |  | || (_| || | | || |_ | |\__ \  / __/  | |_| || || |_ |  _  || |_| || |_) |
 |_|  |_| \__,_||_| |_| \__||_||___/ |_____|  \____||_| \__||_| |_| \__,_||_.__/ 

");

        $hasConfig = file_exists($this->configPath);

        if ($hasConfig) {
            render(<<<'HTML'
<div class="mb-1">
    <div class="mx-1 px-1 bg-yellow-500 text-gray-900">
        <strong>! Config file already exists !</strong>
    </div>
    <div class="mx-1 px-1 bg-yellow-500 text-gray-900">
        <strong>! If you continue your config will be overwritten !</strong>
    </div>
</div>
HTML);

            if ($this->ask(' Are you sure you want to continue? [Y/n]', 'n') !== 'Y') {
                return 1;
            }
        }

        $config = [];

        $this->info("\n Please enter the URL of your Mantis installation (including http:// or https://):");

        $config['mantisUrl'] = $this->ask(" >");

        $parsedUrl = parse_url($config['mantisUrl']);

        if ($parsedUrl === false || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            return 1;
        }

        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        $config['mantisUrl'] = "{$parsedUrl['scheme']}://{$parsedUrl['host']}$port/";

        // Check if something is available on the given URL
        // If not, we assume that the URL is wrong
        $headers = @get_headers($config['mantisUrl']);
        if (!$headers || $headers[0] === 'HTTP/1.1 404 Not Found') {
            return 1;
        }

        $this->info("\n Please enter an API token to got from {$config['mantisUrl']}api_tokens_page.php:");

        $mantisToken = $this->secret(" >");

        return 0;
    }
}
