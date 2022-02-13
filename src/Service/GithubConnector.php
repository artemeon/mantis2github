<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\GithubIssue;
use GuzzleHttp\Client;

class GithubConnector
{
    private ConfigValues $config;

    public function __construct(ConfigValues $config)
    {
        $this->config = $config;
    }


    public function readIssue(int $number): GithubIssue
    {
        $response = $this->getDefaultClient()->get('https://api.github.com/repos/' . $this->config->getGithubRepo() . '/issues/' . $number);

        $result = json_decode($response->getBody(), true);

        return new GithubIssue(
            $result['id'],
            $result['number'],
            $result['title'],
            $result['body'] ?? '',
            'https://github.com/' . $this->config->getGithubRepo() . '/issues/' . $result['number']
        );
    }


    public function createIssue(GithubIssue $issue): GithubIssue
    {
        $response = $this->getDefaultClient()->post('https://api.github.com/repos/' . $this->config->getGithubRepo() . '/issues', [
            'body' => json_encode(['title' => $issue->getTitle(), 'body' => $issue->getDescription()])
        ]);

        $result = json_decode($response->getBody(), true);

        $issue = new GithubIssue(
            $result['id'],
            $result['number'],
            $result['title'],
            $result['body'],
            'https://github.com/' . $this->config->getGithubRepo() . '/issues/' . $result['number']
        );

        return $issue;
    }


    private function getDefaultClient(): Client
    {
        return new Client([
                              'headers' => [
                                  'Authorization' => 'token ' . $this->config->getGithubToken(),
                                  'accept' => 'application/vnd.github.v3+json'
                              ]
                          ]);
    }
}
