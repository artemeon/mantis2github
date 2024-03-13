<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\MantisIssue;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\ExpectedValues;
use JsonException;
use RuntimeException;

class MantisConnector
{
    private Client $client;

    public function __construct(private ?ConfigValues $config)
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => $this->config?->getMantisToken(),
                'Content-Type' => 'application/json',
            ],
            'verify' => false,
            'base_uri' => rtrim($this->config?->getMantisUrl() ?? '', '/') . '/api/rest/issues/',
        ]);
    }

    /**
     * @return MantisIssue[]
     */
    final public function fetchIssues(int $filterId = null): array
    {
        try {
            $query = http_build_query(array_filter([
                'filter_id' => $filterId,
                'page_size' => 400,
            ], static fn (mixed $value) => $value !== null));

            $response = $this->client->get($query ? '?' . $query : '');
            /**
             * @var array{
             *     issues: array{
             *         id: int,
             *         summary: string,
             *         description: string,
             *         project: array{
             *             name: string,
             *         },
             *         status: array{
             *             name: string,
             *             label: string,
             *         },
             *         resolution: array{
             *             name: string,
             *         },
             *         handler: array{
             *             real_name: ?string,
             *             name: ?string,
             *         },
             *         custom_fields: array{
             *             field: array{
             *                 name: string,
             *                 id: ?int
             *             },
             *             value: string
             *         }[],
             *     }[]
             * } $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
            return [];
        }

        $output = [];
        foreach ($result['issues'] as $issue) {
            $output[] = $this->mapIssue($issue, 'label');
        }

        return $output;
    }

    final public function readIssue(int $number): ?MantisIssue
    {
        try {
            $response = $this->client->get((string) $number);
            /**
             * @var array{
             *     issues: array{
             *         id: int,
             *         summary: string,
             *         description: string,
             *         project: array{
             *             name: string,
             *         },
             *         status: array{
             *             name: string,
             *             label: string,
             *         },
             *         resolution: array{
             *             name: string,
             *         },
             *         handler: array{
             *             real_name: ?string,
             *             name: ?string,
             *         },
             *         custom_fields: array{
             *             field: array{
             *                 name: string,
             *                 id: ?int
             *             },
             *             value: string
             *         }[],
             *     }[]
             * } $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
            return null;
        }

        return $this->mapIssue($result['issues'][0]);
    }

    /**
     * @throws JsonException
     */
    final public function patchUpstreamField(MantisIssue $issue): bool
    {
        $body = json_encode([
            'custom_fields' => [
                [
                    'field' => [
                        'id' => $issue->getUpstreamTicketFieldId(),
                        'name' => $issue->getUpstreamTicketFieldName(),
                    ],
                    'value' => $issue->getUpstreamTicket(),
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $this->client->patch(
                (string) $issue->getId(),
                [
                    'body' => $body,
                ],
            );

            return true;
        } catch (Exception | GuzzleException) {
            return false;
        }
    }

    /**
     * @param array{
     *     id: int,
     *     summary: string,
     *     description: string,
     *     project: array{
     *         name: string,
     *     },
     *     status: array{
     *         name: string,
     *         label: string,
     *     },
     *     resolution: array{
     *         name: string,
     *     },
     *     handler: array{
     *         real_name: ?string,
     *         name: ?string,
     *     },
     *     custom_fields: array{
     *          field: array{
     *              name: string,
     *              id: ?int
     *          },
     *          value: string
     *      }[],
     * } $data
     */
    private function mapIssue(array $data, #[ExpectedValues(['name', 'label'])] string $status = 'name'): MantisIssue
    {
        if (!$this->config) {
            throw new RuntimeException('Config is missing.');
        }

        $mantisBaseUrl = $this->config->getMantisUrl();
        if (!str_ends_with($mantisBaseUrl, '/')) {
            $mantisBaseUrl .= '/';
        }

        $issue = new MantisIssue(
            id: $data['id'],
            summary: $data['summary'],
            description: $data['description'],
            project: $data['project']['name'],
            status: $data['status'][$status],
            resolution: $data['resolution']['name'],
            assignee: $data['handler']['real_name'] ?? $data['handler']['name'] ?? null,
            issueUrl: $mantisBaseUrl . 'view.php?id=' . $data['id'],
        );
        $this->updateUpstreamFieldsIssue($data, $issue);

        return $issue;
    }

    /**
     * @param array{
     *     custom_fields: array{
     *         field: array{
     *             name: string,
     *             id: ?int
     *         },
     *         value: string
     *     }[],
     * } $issue
     */
    private function updateUpstreamFieldsIssue(array $issue, MantisIssue $mantisIssue): void
    {
        foreach ($issue['custom_fields'] as $field) {
            if ($field['field']['name'] === 'Upstream Ticket') {
                $mantisIssue->setUpstreamTicketFieldName($field['field']['name']);
                $mantisIssue->setUpstreamTicketFieldId($field['field']['id']);
                $mantisIssue->setUpstreamTicket($field['value']);
            }
        }
    }
}
