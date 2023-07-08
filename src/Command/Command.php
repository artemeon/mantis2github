<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Config\ConfigReader;

class Command extends \Artemeon\Console\Command
{
    final protected function header(): void
    {
        $this->output->write(
            '
  __  __                _    _        <comment>____</comment>     ____  _  _    _   _         _
 |  \/  |  __ _  _ __  | |_ (_) ___  <comment>|___ \ </comment>  / ___|(_)| |_ | | | | _   _ | |__
 | |\/| | / _` || \'_ \ | __|| |/ __|   <comment>__) |</comment> | |  _ | || __|| |_| || | | || \'_ \
 | |  | || (_| || | | || |_ | |\__ \  <comment>/ __/</comment>  | |_| || || |_ |  _  || |_| || |_) |
 |_|  |_| \__,_||_| |_| \__||_||___/ <comment>|_____|</comment>  \____||_| \__||_| |_| \__,_||_.__/


'
        );
    }

    final protected function checkConfig(): void
    {
        $config = (new ConfigReader())->read();

        if (!$config) {
            $this->newLine();
            $this->warn('You have not configured mantis2github yet');
            $this->warn('Please run "mantis2github configure" to get started');
            $this->newLine();

            exit(self::INVALID);
        }
    }
}
