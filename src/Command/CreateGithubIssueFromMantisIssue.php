<?php

declare(strict_types=1);

namespace Artemeon\M2G\Command;

use Artemeon\M2G\Dto\SyncResult;
use Artemeon\M2G\Dto\SyncStatus;
use Artemeon\M2G\Service\IssueSyncService;
use JsonException;
use Symfony\Component\Console\Helper\Table;

class CreateGithubIssueFromMantisIssue extends Command
{
    protected string $signature = 'sync {ids* : Mantis issue IDs}';

    protected ?string $description = 'Synchronize a list of Mantis issues to GitHub';

    public function __construct(private readonly IssueSyncService $issueSyncService)
    {
        parent::__construct();
    }

    /**
     * @throws JsonException
     */
    public function __invoke(): int
    {
        $this->checkConfig();

        $this->title('Mantis 2 GitHub Sync');
        /** @var string[]|bool|string|null $idsArgument */
        $idsArgument = $this->argument('ids');

        if (!is_array($idsArgument)) {
            return self::INVALID;
        }

        $ids = array_unique($idsArgument);
        $message = count($ids) !== 1 ? 'Creating issues ...' : 'Creating issue ...';

        $this->newLine();

        /** @var SyncResult[] $results */
        $results = [];

        $this->spin(function () use ($ids, &$results): void {
            $results = $this->issueSyncService->sync($ids, force: true);
        }, $message);

        $this->newLine();

        $table = new Table($this->output);
        $table->setHeaders(['', 'Mantis issue ID', 'Message', 'GitHub Issue']);
        foreach ($results as $result) {
            $success = $result->status === SyncStatus::Synced;
            $icon = $success ? '<info>✓</info>' : '<error>✕</error>';
            $detail = $result->detail ?? $result->status->value;
            $message = $success ? "<info>$detail</info>" : "<error>$detail</error>";

            $table->addRow([$icon, $result->mantisId, $message, $result->githubUrl ?? '']);
        }
        $table->render();

        $this->newLine();

        return self::SUCCESS;
    }
}
