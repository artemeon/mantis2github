<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(new class() {

    public function main()
    {
        require './vendor/autoload.php';

        $configVals = (new \Artemeon\M2G\Config\ConfigReader())->read();
        $githubConnector = new \Artemeon\M2G\Service\GithubConnector($configVals);
        $mantisConnector = new \Artemeon\M2G\Service\MantisConnector($configVals);

        $app = new \Symfony\Component\Console\Application('Mantis 2 Github', 0.1);
        $app->add(new \Artemeon\M2G\Command\ReadMantisIssueCommand($mantisConnector));
        $app->add(new \Artemeon\M2G\Command\ReadGithubIssueCommand($githubConnector));
        $app->add(new \Artemeon\M2G\Command\CreateGithubIssueFromMantisIssue($mantisConnector, $githubConnector));
        $app->run();
    }


})->main();





