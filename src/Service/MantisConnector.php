<?php
/*
 * This file is part of the Artemeon Core - Web Application Framework.
 *
 * (c) Artemeon <www.artemeon.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artemeon\M2G\Service;

use Artemeon\M2G\Config\ConfigValues;
use Artemeon\M2G\Dto\MantisIssue;
use GuzzleHttp\Client;

class MantisConnector
{
    private ?ConfigValues $config;

    public function __construct(?ConfigValues $config)
    {
        $this->config = $config;
    }

    public function readIssue(int $number): ?MantisIssue
    {
        try {
            $response = $this->getDefaultClient()->get(
                rtrim($this->config->getMantisUrl(), '/') . '/api/rest/issues/' . $number
            );
            $result = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return null;
        }

        $issue = new MantisIssue(
            $result['issues'][0]['id'],
            $result['issues'][0]['summary'],
            $result['issues'][0]['description'],
            $result['issues'][0]['project']['name'],
            $result['issues'][0]['status']['name'],
            $result['issues'][0]['resolution']['name'],
            $result['issues'][0]['handler']['real_name'] ?? $result['issues'][0]['handler']['name'] ?? null,
            $this->config->getMantisUrl() . '/view.php?id=' . $result['issues'][0]['id'],
            null,
            null,
            null,
        );
        $this->updateUpstreamFieldsIssue($result['issues'][0], $issue);

        return $issue;
    }

    public function patchUpstreamField(MantisIssue $issue)
    {
        $jsonEncode = json_encode([
            'custom_fields' => [
                [
                    'field' => [
                        'id' => $issue->getUpstreamTicketFieldId(),
                        'name' => $issue->getUpstreamTicketFieldName()
                    ],
                    'value' => $issue->getUpstreamTicket()
                ]
            ]
        ]);

        $response = $this->getDefaultClient()->patch(
            $this->config->getMantisUrl() . '/api/rest/issues/' . $issue->getId(),
            [
                'body' => $jsonEncode
            ]
        );
    }

    private function updateUpstreamFieldsIssue(array $issue, MantisIssue $mantisIssue): void
    {
        foreach ($issue['custom_fields'] as $aField) {
            if ($aField['field']['name'] === 'Upstream Ticket') {
                $mantisIssue->setUpstreamTicketFieldName($aField['field']['name']);
                $mantisIssue->setUpstreamTicketFieldId($aField['field']['id']);
                $mantisIssue->setUpstreamTicket($aField['value']);
            }
        }
    }

    private function getDefaultClient(): Client
    {
        return new Client([
            'headers' => [
                'Authorization' => $this->config->getMantisToken(),
                'Content-Type' => 'application/json'
            ]
        ]);
    }

}
