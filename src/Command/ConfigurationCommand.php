<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Config\ConfigReader;
use Symfony\Component\Yaml\Yaml;

use function Termwind\{render, terminal};

class ConfigurationCommand extends Command
{
    protected string $signature = 'configure {file? : The config.yaml to use for setting up the tool.}';
    protected ?string $description = 'Configure the tool';

    protected string $configPath = __DIR__ . '/../../../config.yaml';

    /**
     * @var array{
     *     mantisUrl: string,
     *     mantisToken: string,
     *     githubToken: string,
     *     githubRepository: string,
     * }
     */
    protected array $config = [
        'mantisUrl' => '',
        'mantisToken' => '',
        'githubToken' => '',
        'githubRepository' => '',
    ];

    public function __invoke(): int
    {
        $this->readExistingConfigFromPath();

        terminal()->clear();

        $this->header();

        $hasConfig = (new ConfigReader())->read();

        if ($hasConfig !== null) {
            $this->warn('Configuration file already exists');
            $this->warn('If you continue your configuration will be overwritten');

            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Alright!');

                return self::INVALID;
            }
        }

        terminal()->clear();

        $this->header();

        $this->askForMantisUrl();
        $this->askForMantisToken();
        $this->askForGitHubToken();
        $this->askForGitHubRepository();
        $this->saveConfig();

        return self::SUCCESS;
    }

    private function askForMantisUrl(): void
    {
        $mantisUrl = $this->ask(
            label: 'The URL of your Mantis installation',
            placeholder: 'E.g. https://tickets.company.tld/',
            required: true,
            validate: function (string $value) {
                $parsedUrl = parse_url($value);

                if ($parsedUrl === false) {
                    return 'You must enter a valid URL.';
                }

                if (!isset($parsedUrl['scheme'])) {
                    return 'The URL must include a scheme like "http://" or "https://".';
                }

                if (!isset($parsedUrl['host'])) {
                    return 'The URL must include a valid host.';
                }

                $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                $mantisUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}$port/";

                $headers = @get_headers($mantisUrl);
                if (!$headers || $headers[0] === 'HTTP/1.1 404 Not Found') {
                    return 'The given URL is unreachable. If this error persists, please check your internet connection.';
                }

                return null;
            },
        );

        $parsedUrl = parse_url($mantisUrl);
        if ($parsedUrl) {
            $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
            $mantisUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}$port/";

            $this->config['mantisUrl'] = $mantisUrl;
        }
    }

    private function askForMantisToken(): void
    {
        $this->info("Head over to {$this->config['mantisUrl']}api_tokens_page.php and create a new API token.");

        $this->config['mantisToken'] = $this->password(
            label: 'Mantis API Token',
            required: true,
        );
    }

    private function askForGitHubToken(): void
    {
        $this->info('Head over to https://github.com/settings/tokens, create a new personal access token with the `repo` scope.');

        $this->config['githubToken'] = $this->password(
            label: 'GitHub Personal Access Token',
            required: true,
            validate: fn (string $value) => !str_starts_with($value, 'ghp_') && str_starts_with($value, 'github_pat_')
                ? 'The provided value is not a valid GitHub PAT.'
                : null,
        );
    }

    private function askForGitHubRepository(): void
    {
        $this->config['githubRepository'] = $this->ask(
            label: 'GitHub Repository(e.g. user/repository)',
            placeholder: 'E.g. user/repository',
            required: true,
            validate: fn (string $value) => count(explode('/', $value)) !== 2
                ? 'Invalid repository name.'
                : null,
        );
    }

    private function saveConfig(): void
    {
        $stub = file_get_contents(__DIR__ . '/../../stubs/config.yaml.stub');
        if (!$stub) {
            return;
        }

        $configContent = preg_replace_callback('/{{([a-z0-9_]+)}}/i', fn ($matches) => $this->config[$matches[1]] ?? '', $stub);

        file_put_contents($this->configPath, $configContent);

        render(
            <<<HTML
<div class="mb-1 ml-2 px-1 bg-green-500 text-gray-900">
    <strong>🚀 Ready for liftoff! 🚀</strong>
</div>
HTML
        );

        $this->success('Synchronize your first issue by running `mantis2github sync`!');
    }

    private function readExistingConfigFromPath(): void
    {
        $file = $this->argument('file');

        if (!$file) {
            return;
        }

        if (!is_string($file) || !file_exists($file)) {
            $this->error('The given config file does not exist.');

            exit(1);
        }

        /**
         * @var array{
         *     MANTIS_URL?: string,
         *     MANTIS_TOKEN?: string,
         *     GITHUB_TOKEN?: string,
         *     GITHUB_REPOSITORY?: string,
         * } $config
         */
        $config = Yaml::parseFile($file);

        if (!isset($config['MANTIS_URL'], $config['MANTIS_TOKEN'], $config['GITHUB_TOKEN'], $config['GITHUB_REPOSITORY'])) {
            $this->error('The given config file is incomplete.');
            $this->info('Please configure the tool without the file parameter.');

            exit(1);
        }

        $this->config = [
            'mantisUrl' => $config['MANTIS_URL'],
            'mantisToken' => $config['MANTIS_TOKEN'],
            'githubToken' => $config['GITHUB_TOKEN'],
            'githubRepository' => $config['GITHUB_REPOSITORY'],
        ];

        $this->saveConfig();

        exit(self::SUCCESS);
    }
}
