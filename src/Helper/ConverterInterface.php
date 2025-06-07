<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use Artemeon\M2G\Command\IssuesListCommand;
use Artemeon\M2G\Dto\MantisTicket;

interface ConverterInterface
{
    /**
     * @param MantisTicket[] $tickets
     * @param array<string, mixed> $githubIssue
     */
    public static function convert(IssuesListCommand $issuesListCommand, array $tickets, array $githubIssue): void;
}
