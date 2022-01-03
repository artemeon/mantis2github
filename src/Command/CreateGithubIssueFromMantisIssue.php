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

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Service\GithubConnector;
use Artemeon\M2G\Service\MantisConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateGithubIssueFromMantisIssue extends Command
{
    private GithubConnector $githubConnector;
    private MantisConnector $mantisConnector;

    public function __construct(MantisConnector $mantisConnector, GithubConnector $githubConnector)
    {
        parent::__construct();
        $this->mantisConnector = $mantisConnector;
        $this->githubConnector = $githubConnector;
    }


    protected function configure()
    {
        $this->setName('mantis2github');
        $this->setDescription('creates a github issue from a mantis issue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Mantis 2 Github Sync, creates a new Github issue');
        $question = new Question('Mantis ID:');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $id = $helper->ask($input, $output, $question);

        if (!is_numeric($id)) {
            $output->writeln('<error>Provide issue id</error>');
        }

        $mantisIssue = $this->mantisConnector->readIssue((int)$id);
        $output->writeln('Read mantis issue with ID ' . $mantisIssue->getId());

        if (!empty($mantisIssue->getUpstreamTicket())) {
            $output->writeln('Githubticket already exists: ' . $mantisIssue->getUpstreamTicket());
            return 1;
        }

        $newGithubIssue = GithubIssue::fromMantisIssue($mantisIssue);

        $newGithubIssue = $this->githubConnector->createIssue($newGithubIssue);
        $output->writeln('Created github issue with ID ' . $newGithubIssue->getNumber());

        $mantisIssue->setUpstreamTicket($newGithubIssue->getIssueUrl());
        $this->mantisConnector->patchUpstreamField($mantisIssue);
        $output->writeln('Updated mantis upstream issue url to ' . $mantisIssue->getUpstreamTicket());


        return 0;
    }


}