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
use Artemeon\M2G\Service\MantisConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ReadMantisIssueCommand extends Command
{
    private MantisConnector $mantisConnector;

    public function __construct(MantisConnector $mantisConnector)
    {
        parent::__construct();
        $this->mantisConnector = $mantisConnector;
    }


    protected function configure()
    {
        $this->setName('mantis-read');
        $this->setDescription('read details of a mantis issue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Mantis Details');
        $question = new Question('Mantis ID: ');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $id = $helper->ask($input, $output, $question);

        if (!is_numeric($id)) {
            $output->writeln('<error>Provide issue id</error>');
        }

        $issue = $this->mantisConnector->readIssue((int)$id);

        $table = new Table($output);
        $table->addRow(['ID', $issue->getId()]);
        $table->addRow(['Summary', $issue->getSummary()]);
        $table->addRow(['GitHub Issue URL', $issue->getUpstreamTicket()]);
        $table->render();

        return 0;
    }


}
