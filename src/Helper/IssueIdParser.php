<?php

declare(strict_types=1);

namespace Artemeon\M2G\Helper;

use InvalidArgumentException;

class IssueIdParser
{
    final public static function parse(int | string | null $id, ?string $url): int
    {
        if ($id !== null && $id !== '') {
            if (is_int($id)) {
                return self::ensurePositive($id);
            }

            if (ctype_digit($id)) {
                return self::ensurePositive((int) $id);
            }

            throw new InvalidArgumentException('Parameter "id" must be a positive integer.');
        }

        if ($url !== null && $url !== '') {
            $query = parse_url($url, \PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                $candidate = $params['id'] ?? null;
                if (is_string($candidate) && ctype_digit($candidate)) {
                    return self::ensurePositive((int) $candidate);
                }
            }

            throw new InvalidArgumentException('Could not extract a numeric "id" query parameter from the URL.');
        }

        throw new InvalidArgumentException('Either "id" or "url" must be provided.');
    }

    private static function ensurePositive(int $id): int
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Parameter "id" must be a positive integer.');
        }

        return $id;
    }
}
