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
use Symfony\Component\Console\Helper\Table;
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
        $this->setName('sync');
        $this->setDescription('Synchronize a Mantis Issue to GitHub');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Mantis 2 Github Sync, creates a new Github issue');
        $question = new Question('Mantis ID: ');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $id = $helper->ask($input, $output, $question);

        if (!is_numeric($id)) {
            $output->writeln('<error>Please provide a valid issue id.</error>');

            return 1;
        }

        $output->writeln("Reading mantis issue with ID $id ...");

        $mantisIssue = $this->mantisConnector->readIssue((int)$id);

        if ($mantisIssue === null) {
            $output->writeln("<error>No issue found with id $id.</error>");

            return 1;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Summary']);
        $table->addRow([$mantisIssue->getId(), $mantisIssue->getSummary()]);
        $table->render();

        if (!empty($mantisIssue->getUpstreamTicket())) {
            $output->writeln("<comment>GitHub issue already exists: {$mantisIssue->getUpstreamTicket()}</comment>");

            return 1;
        }

        $confirmationQuestion = new Question('Do you want to create a new issue on GitHub? [Y/n] ', 'n');

        $confirmation = $helper->ask($input, $output, $confirmationQuestion);

        if ($confirmation !== 'Y') {
            $output->writeln('<error>Aborted</error>');

            return 1;
        }

        $output->writeln('<info>Creating new GitHub issue ...</info>');

        $newGithubIssue = GithubIssue::fromMantisIssue($mantisIssue);

        $newGithubIssue = $this->githubConnector->createIssue($newGithubIssue);
        $output->writeln("<info>Successfully created GitHub issue #{$newGithubIssue->getId()}.</info>");

        $output->writeln('Updating upstream ticket at mantis issue ...');

        $mantisIssue->setUpstreamTicket($newGithubIssue->getIssueUrl());
        $this->mantisConnector->patchUpstreamField($mantisIssue);

        $output->writeln("<info>Successfully updated Mantis upstream issue url to {$mantisIssue->getUpstreamTicket()}.</info>");

        return 0;
    }
}
