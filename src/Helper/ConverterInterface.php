<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use Artemeon\M2G\Command\IssuesListCommand;
use Artemeon\M2G\Dto\MantisIssue;

interface ConverterInterface
{
    /**
     * @param MantisIssue[] $mantisIssues
     */
    public static function convert(IssuesListCommand $command, array $mantisIssues, array $githubResult): void;
}
