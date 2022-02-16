<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Service\GithubConnector;

use function Termwind\render;

class ReadGithubIssueCommand extends Command
{
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
        $this->setName('read:github');
        $this->setDescription('Read details of a GitHub issue');
    }

    protected function handle(): int
    {
        render(<<<HTML
<div class="my-1 mx-1 px-2 bg-green-500 text-gray-900">
    GitHub Issue Details
</div>
HTML);

        $id = $this->ask(' GitHub Issue ID:');

        if (!is_numeric($id)) {
            $this->error('Provide issue id');
            return 1;
        }

        $this->info('Fetching issue details...');

        $issue = $this->githubConnector->readIssue((int)$id);

        render(<<<HTML
<table>
    <tbody>
        <tr>
            <th>ID</th>
            <td>{$issue->getId()}</td>
        </tr>
        <tr>
            <th>Number</th>
            <td>#{$issue->getNumber()}</td>
        </tr>
        <tr>
            <th>Title</th>
            <td>{$issue->getTitle()}</td>
        </tr>
        <tr>
            <th>URL</th>
            <td>{$issue->getIssueUrl()}</td>
        </tr>
    </tbody>
</table>
HTML);

        return 0;
    }


}
