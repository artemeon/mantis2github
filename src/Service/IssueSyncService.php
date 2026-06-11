<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Dto\GithubIssue;
use Artemeon\M2G\Dto\SyncResult;
use Artemeon\M2G\Dto\SyncStatus;
use JsonException;

readonly class IssueSyncService
{
    public function __construct(
        private MantisConnector $mantisConnector,
        private GithubConnector $githubConnector,
        private string $mantisBaseUrl,
    ) {
    }

    /**
     * Synchronize the given Mantis issues to GitHub.
     *
     * @param array<int|string> $ids
     *
     * @throws JsonException
     * @return SyncResult[]
     */
    final public function sync(array $ids, bool $force = false): array
    {
        $labels = array_map(
            static fn (array $label): string => $label['name'],
            $this->githubConnector->getLabels(),
        );

        $results = [];
        foreach (array_unique($ids) as $id) {
            $results[] = $this->syncIssue((int) $id, $force, $labels);
        }

        return $results;
    }

    /**
     * @param string[] $labels
     *
     * @throws JsonException
     */
    private function syncIssue(int $id, bool $force, array $labels): SyncResult
    {
        $mantisIssue = $this->mantisConnector->readIssue($id);

        if ($mantisIssue === null) {
            return new SyncResult($id, SyncStatus::MantisNotFound, detail: 'Mantis issue not found.');
        }

        if (!$force && $mantisIssue->upstreamTicket !== null && trim($mantisIssue->upstreamTicket) !== '') {
            return new SyncResult(
                $id,
                SyncStatus::Skipped,
                githubUrl: $mantisIssue->upstreamTicket,
                detail: 'Mantis issue is already synced. Pass "force" to sync it again.',
            );
        }

        $newGithubIssue = GithubIssue::fromMantisIssue($mantisIssue, $this->mantisBaseUrl);

        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     color: string,
         * }[] $filteredLabels
         */
        $filteredLabels = array_values(
            array_filter($labels, static fn (string $label): bool => strtolower($label) === strtolower($mantisIssue->project)),
        );

        $newGithubIssue->labels = $filteredLabels;
        $newGithubIssue = $this->githubConnector->createIssue($newGithubIssue);

        if ($newGithubIssue === null) {
            return new SyncResult($id, SyncStatus::GithubCreateFailed, detail: 'GitHub issue could not be created.');
        }

        $mantisIssue->upstreamTicket = trim($mantisIssue->upstreamTicket . ' ' . $newGithubIssue->issueUrl);

        if ($this->mantisConnector->patchUpstreamField($mantisIssue) === false) {
            return new SyncResult(
                $id,
                SyncStatus::UpstreamPatchFailed,
                githubUrl: $newGithubIssue->issueUrl,
                detail: 'GitHub issue was created but the upstream ticket URL could not be updated on Mantis.',
            );
        }

        return new SyncResult(
            $id,
            SyncStatus::Synced,
            githubUrl: $newGithubIssue->issueUrl,
            detail: 'Mantis issue has been synchronized.',
        );
    }
}
