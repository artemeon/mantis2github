<?php

namespace Artemeon\M2G\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->handle();
    }

    protected function ask(string $question, string $default = null, bool $hidden = false)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $trailingSpace = str_ends_with($question, ' ') ? '' : ' ';
        $question = new Question($question . $trailingSpace, $default);
        $question->setHidden($hidden);

        return $helper->ask($this->input, $this->output, $question);
    }

    protected function secret(string $question, string $default = null)
    {
        return $this->ask($question, $default, true);
    }

    protected function info(string $message)
    {
        $this->output->writeln($message);
    }

    protected function error(string $message)
    {
        $this->output->writeln("<error>$message</error>");
    }

    protected function success(string $message)
    {
        $this->output->writeln("<info>$message</info>");
    }

    protected function warn(string $message)
    {
        $this->output->writeln("<comment>$message</comment>");
    }

    abstract protected function handle(): int;
}
