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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
        $this->setName('github-read');
        $this->setDescription('read details of a mantis issue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Github Details');
        $question = new Question('Github Issue ID:');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $id = $helper->ask($input, $output, $question);

        if (!is_numeric($id)) {
            $output->writeln('<error>Provide issue id</error>');
        }

        $issue = $this->githubConnector->readIssue((int)$id);

        $output->writeln('------------------------------------------');
        $output->writeln('ID:              ' . $issue->getId());
        $output->writeln('Number:          ' . $issue->getNumber());
        $output->writeln('Summary:         ' . $issue->getTitle());
        $output->writeln('------------------------------------------');

        return 0;
    }


}