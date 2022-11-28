<?php

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\MantisIssue;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MantisConnector
{
    private Client $client;

    public function __construct(private ?ConfigValues $config)
    {
        if (!$config) {
            return;
        }
        $this->client = new Client([
            'headers' => [
                'Authorization' => $this->config->getMantisToken(),
                'Content-Type' => 'application/json',
            ],
            'verify' => false,
            'base_uri' => rtrim($this->config->getMantisUrl(), '/') . '/api/rest/issues/',
        ]);
    }

    public function readIssue(int $number): ?MantisIssue
    {
        try {
            $response = $this->client->get((string) $number);
            $result = json_decode($response->getBody(), true);
        } catch (GuzzleException | Exception) {
            return null;
        }

        $mantisBaseUrl = $this->config->getMantisUrl();
        if (!str_ends_with($mantisBaseUrl, '/')) {
            $mantisBaseUrl .= '/';
        }

        $issue = new MantisIssue(
            id: $result['issues'][0]['id'],
            summary: $result['issues'][0]['summary'],
            description: $result['issues'][0]['description'],
            project: $result['issues'][0]['project']['name'],
            status: $result['issues'][0]['status']['name'],
            resolution: $result['issues'][0]['resolution']['name'],
            assignee: $result['issues'][0]['handler']['real_name'] ?? $result['issues'][0]['handler']['name'] ?? null,
            issueUrl: $mantisBaseUrl . 'view.php?id=' . $result['issues'][0]['id'],
        );
        $this->updateUpstreamFieldsIssue($result['issues'][0], $issue);

        return $issue;
    }

    public function patchUpstreamField(MantisIssue $issue): bool
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
        ]);

        try {
            $this->client->patch(
                (string) $issue->getId(),
                [
                    'body' => $body,
                ],
            );
            return true;
        } catch (GuzzleException | Exception) {
            return false;
        }
    }

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
