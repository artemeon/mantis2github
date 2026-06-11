<?php

declare(strict_types=1);

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\MantisAttachment;
use Artemeon\M2G\Dto\MantisIssue;
use Artemeon\M2G\Dto\MantisNote;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\ExpectedValues;
use JsonException;
use RuntimeException;

class MantisConnector
{
    private readonly Client $client;

    public function __construct(private readonly ?ConfigValues $config)
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
    final public function fetchIssues(?int $filterId = null): array
    {
        try {
            $query = http_build_query(array_filter([
                'filter_id' => $filterId,
                'page_size' => 400,
            ], static fn (mixed $value) => $value !== null));

            $response = $this->client->get($query !== '' && $query !== '0' ? '?' . $query : '');
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
             *         attachments?: array{
             *             id: int,
             *             filename: string,
             *             size: int,
             *             content_type?: ?string,
             *         }[],
             *         notes?: array{
             *             id: int,
             *             reporter: array{
             *                 real_name: ?string,
             *                 name: ?string,
             *             },
             *             text: string,
             *             created_at?: ?string,
             *             view_state?: array{
             *                 name?: ?string,
             *             },
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
     * @return MantisAttachment[]|null Null on fetch failure, empty array if the issue has no files.
     */
    final public function listIssueFiles(int $issueId): ?array
    {
        try {
            $response = $this->client->get($issueId . '/files');
            /**
             * @var array{
             *     files: array{
             *         id: int,
             *         filename: string,
             *         size: int,
             *         content_type?: ?string,
             *     }[]
             * } $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
            return null;
        }

        $attachments = [];
        foreach ($result['files'] as $file) {
            $attachments[] = new MantisAttachment(
                id: $file['id'],
                filename: $file['filename'],
                size: $file['size'],
                contentType: $file['content_type'] ?? null,
            );
        }

        return $attachments;
    }

    final public function fetchIssueFile(int $issueId, int $fileId): ?MantisAttachment
    {
        try {
            $response = $this->client->get($issueId . '/files/' . $fileId);
            /**
             * @var array{
             *     files: array{
             *         id: int,
             *         filename: string,
             *         size: int,
             *         content_type?: ?string,
             *         content?: ?string,
             *     }[]
             * } $result
             */
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception | GuzzleException) {
            return null;
        }

        if ($result['files'] === []) {
            return null;
        }

        $file = $result['files'][0];

        return new MantisAttachment(
            id: $file['id'],
            filename: $file['filename'],
            size: $file['size'],
            contentType: $file['content_type'] ?? null,
            contentBase64: $file['content'] ?? null,
        );
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
                        'id' => $issue->upstreamTicketFieldId,
                        'name' => $issue->upstreamTicketFieldName,
                    ],
                    'value' => $issue->upstreamTicket,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            $this->client->patch(
                (string) $issue->id,
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
     *     attachments?: array{
     *         id: int,
     *         filename: string,
     *         size: int,
     *         content_type?: ?string,
     *     }[],
     *     notes?: array{
     *         id: int,
     *         reporter: array{
     *             real_name: ?string,
     *             name: ?string,
     *         },
     *         text: string,
     *         created_at?: ?string,
     *         view_state?: array{
     *             name?: ?string,
     *         },
     *     }[],
     * } $data
     */
    private function mapIssue(array $data, #[ExpectedValues(['name', 'label'])] string $status = 'name'): MantisIssue
    {
        if ($this->config === null) {
            throw new RuntimeException('Config is missing.');
        }

        $mantisBaseUrl = $this->config->getMantisUrl();
        if (!str_ends_with($mantisBaseUrl, '/')) {
            $mantisBaseUrl .= '/';
        }

        $attachments = [];
        foreach ($data['attachments'] ?? [] as $attachment) {
            $attachments[] = new MantisAttachment(
                id: $attachment['id'],
                filename: $attachment['filename'],
                size: $attachment['size'],
                contentType: $attachment['content_type'] ?? null,
            );
        }

        $notes = [];
        foreach ($data['notes'] ?? [] as $note) {
            $notes[] = new MantisNote(
                id: $note['id'],
                reporter: $note['reporter']['real_name'] ?? $note['reporter']['name'] ?? null,
                text: $note['text'],
                createdAt: $note['created_at'] ?? null,
                viewState: $note['view_state']['name'] ?? null,
            );
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
            attachments: $attachments,
            notes: $notes,
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
                $mantisIssue->upstreamTicketFieldName = $field['field']['name'];
                $mantisIssue->upstreamTicketFieldId = $field['field']['id'];
                $mantisIssue->upstreamTicket = $field['value'];
            }
        }
    }
}
