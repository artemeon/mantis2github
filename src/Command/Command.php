<?php

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Config\ConfigReader;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use function Termwind\render;

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

    protected function arguments(): array
    {
        return $this->input->getArguments();
    }

    protected function argument(string $name)
    {
        return $this->input->getArgument($name);
    }

    protected function options(): array
    {
        return $this->input->getOptions();
    }

    protected function option(string $name)
    {
        return $this->input->getOption($name);
    }

    protected function header(): void
    {
        $this->success(
            '
  __  __                _    _        <comment>____</comment>     ____  _  _    _   _         _     
 |  \/  |  __ _  _ __  | |_ (_) ___  <comment>|___ \ </comment>  / ___|(_)| |_ | | | | _   _ | |__  
 | |\/| | / _` || \'_ \ | __|| |/ __|   <comment>__) |</comment> | |  _ | || __|| |_| || | | || \'_ \ 
 | |  | || (_| || | | || |_ | |\__ \  <comment>/ __/</comment>  | |_| || || |_ |  _  || |_| || |_) |
 |_|  |_| \__,_||_| |_| \__||_||___/ <comment>|_____|</comment>  \____||_| \__||_| |_| \__,_||_.__/ 

'
        );
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
        render(
            <<<HTML
<div class="my-1 ml-1 px-1 bg-red-400 text-white">
    $message
</div>
HTML
        );
    }

    protected function success(string $message)
    {
        $this->output->writeln("<info>$message</info>");
    }

    protected function warn(string $message)
    {
        render(
            <<<HTML
<div class="ml-1 px-1 bg-yellow-500 text-gray-900">
    <strong>! $message !</strong>
</div>
HTML
        );
    }

    protected function checkConfig(): void
    {
        $config = (new ConfigReader())->read();

        if (!$config) {
            $this->info('');
            $this->warn('You have not configured mantis2github yet');
            $this->warn('Please run "mantis2github configure" to get started');
            $this->info('');

            exit(1);
        }
    }

    abstract protected function handle(): int;
}
