<?php

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Config\ConfigReader;
use Artemeon\M2G\Service\GithubConnector;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;

use function Termwind\{render, terminal};

class ConfigurationCommand extends Command
{
    protected string $configPath = __DIR__ . '/../../config.yaml';
    protected array $config = [];

    private GithubConnector $githubConnector;

    /**
     * @param GithubConnector $mantisConnector
     */
    public function __construct(GithubConnector $mantisConnector)
    {
        parent::__construct();
        $this->githubConnector = $mantisConnector;
    }

    protected function configure()
    {
        $this->setName('configure')
            ->setDescription('Configure the tool')
            ->addArgument('file', InputArgument::OPTIONAL, 'The config.yaml to use for setting up the tool.');
    }

    protected function handle(): int
    {
        $this->readExistingConfigFromPath();

        terminal()->clear();

        $this->header();

        $hasConfig = (new ConfigReader())->read();

        if ($hasConfig !== null) {
            $this->warn('Configuration file already exists');
            $this->warn('If you continue your configuration will be overwritten');

            if ($this->ask("\n Are you sure you want to continue? [Y/n]", 'n') !== 'Y') {
                $this->info("\n Alright!\n");
                return 1;
            }
        }

        terminal()->clear();

        $this->header();

        $this->askForMantisUrl();
        $this->askForMantisToken();
        $this->askForGitHubToken();
        $this->askForGitHubRepository();
        $this->saveConfig();

        return 0;
    }

    protected function askForMantisUrl(): void
    {
        $this->info(" Please enter the URL of your Mantis installation (e.g. https://tickets.company.tld):");

        $mantisUrl = $this->ask(" >");

        $parsedUrl = parse_url($mantisUrl);

        if ($parsedUrl === false || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            $this->error("The URL you entered is invalid.");

            $this->askForMantisUrl();
        }

        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        $mantisUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}$port/";

        // Check if something is available on the given URL
        // If not, we assume that the URL is wrong
        $headers = @get_headers($mantisUrl);
        if (!$headers || $headers[0] === 'HTTP/1.1 404 Not Found') {
            $this->error(
                "The given URL is unreachable. If this error persists, please check your internet connection."
            );

            $this->askForMantisUrl();
        }

        $this->config['mantisUrl'] = $mantisUrl;
    }

    protected function askForMantisToken(): void
    {
        $this->info("\n Head over to {$this->config['mantisUrl']}api_tokens_page.php, create a new API token,");
        $this->info(" and enter the token here:");

        $token = $this->secret(" >");

        if (empty($token)) {
            $this->error('The token is empty. Please try again.');

            $this->askForMantisToken();
        }

        $this->config['mantisToken'] = $token;
    }

    protected function askForGitHubToken(): void
    {
        $this->info("\n Head over to https://github.com/settings/tokens, create a new personal access token");
        $this->info(" with the `repo` scope and enter the token here:");

        $token = $this->secret(" >");

        if (empty($token)) {
            $this->error('The token is empty. Please try again.');

            $this->askForGitHubToken();
        }

        $this->config['githubToken'] = $token;
    }

    protected function askForGitHubRepository(): void
    {
        $this->info("\n Enter the GitHub repository you want to create issues for (e.g. user/repository):");

        $repository = $this->ask(" >");

        if (empty($repository) || count(explode('/', $repository)) !== 2) {
            $this->error("The given repository is invalid.");

            $this->askForGitHubRepository();
        }

        $this->config['githubRepository'] = $repository;
    }

    protected function saveConfig(): void
    {
        $stub = file_get_contents(__DIR__ . '/../../stubs/config.yaml.stub');

        $configContent = preg_replace_callback('/{{([a-z0-9_]+)}}/i', function ($matches) {
            return $this->config[$matches[1]] ?? '';
        }, $stub);

        file_put_contents($this->configPath, $configContent);

        render(
            <<<HTML
<div class="my-1 ml-1 px-1 bg-green-500 text-gray-900">
    <strong>🚀 Ready for liftoff! 🚀</strong>
</div>
HTML
        );

        $this->success(" Synchronize your first issue by running `mantis2github sync`!\n");
    }

    protected function readExistingConfigFromPath()
    {
        if (!$this->argument('file')) {
            return;
        }

        if (!file_exists($this->argument('file'))) {
            $this->error('The given config file does not exist.');

            exit(1);
        }

        $config = Yaml::parseFile($this->argument('file'));

        if (!$config['MANTIS_URL'] || !$config['MANTIS_TOKEN'] || !$config['GITHUB_TOKEN'] || !$config['GITHUB_REPOSITORY']) {
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

        exit(0);
    }
}
