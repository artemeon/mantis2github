<?php

declare(strict_types=1);

namespace Artemeon\M2G\Dto;

enum SyncStatus: string
{
    case Synced = 'synced';
    case Skipped = 'skipped';
    case MantisNotFound = 'mantis_not_found';
    case GithubCreateFailed = 'github_create_failed';
    case UpstreamPatchFailed = 'upstream_patch_failed';
}
