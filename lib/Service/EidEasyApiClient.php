<?php

namespace OCA\ElectronicSignatures\Service;

use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;

class EidEasyApiClient
{
    /** @var IClientService */
    private $clientService;

    /** @var IClient|null */
    private $client;

    /** @var string|null */
    private $clientId;

    /** @var string|null */
    private $secret;

    /** @var string */
    private $apiUrl;

    /** @var int */
    private $longPollTimeout = 120000;

    public function __construct(IClientService $clientService)
    {
        $this->clientService = $clientService;
        $this->apiUrl = 'https://id.eideasy.com';
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }

    public function setLongPollTimeout(int $longPollTimeout): void
    {
        $this->longPollTimeout = $longPollTimeout;
    }

    public function startIdentification(string $method, array $data): array
    {
        $params = array_merge([
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'timeout' => $this->longPollTimeout,
        ], $data);

        return $this->sendRequest("/api/identity/$this->clientId/$method/start", $params);
    }

    public function completeIdentification(string $method, array $data): array
    {
        $params = array_merge([
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'timeout' => $this->longPollTimeout,
        ], $data);

        return $this->sendRequest("/api/identity/$this->clientId/$method/complete", $params);
    }

    public function createEseal($docId): array
    {
        $timestamp = time();
        $uri = '/api/signatures/e-seal/create';
        $hmacData = "$this->clientId$this->secret$docId$timestamp$uri";
        $hmac = hash_hmac('SHA256', $hmacData, (string)$this->secret);
        $params = [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'doc_id' => $docId,
            'timestamp' => $timestamp,
            'hmac' => $hmac,
        ];

        return $this->sendRequest($uri, $params);
    }

    public function getSignedFile(string $docId): array
    {
        return $this->sendRequest('/api/signatures/download-signed-file', [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'doc_id' => $docId,
        ]);
    }

    public function prepareFiles(array $files, array $parameters = []): array
    {
        $data = [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'container_type' => $parameters['container_type'] ?? 'asice',
            'baseline' => $parameters['baseline'] ?? 'LT',
            'files' => $files,
            'show_visual' => $parameters['show_visual'] ?? true,
            'nodownload' => $parameters['nodownload'] ?? false,
            'noemails' => $parameters['noemails'] ?? false,
            'hide_preview_download' => $parameters['hide_preview_download'] ?? false,
        ];

        $data = $this->addPrepareFileSigningParams($data, $parameters);

        return $this->sendRequest('/api/signatures/prepare-files-for-signing', $data);
    }

    public function prepareAsiceForSigning(string $file, array $parameters = []): array
    {
        $data = [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'container' => $file,
            'filename' => $parameters['filename'] ?? 'filename.asice',
        ];

        $data = $this->addPrepareFileSigningParams($data, $parameters);

        return $this->sendRequest('/api/signatures/prepare-add-signature', $data);
    }

    public function downloadSignedFile(string $docId): array
    {
        return $this->sendRequest('/api/signatures/download-signed-file', [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'doc_id' => $docId,
        ]);
    }

    public function downloadAuditTrail(string $docId): array
    {
        return $this->sendRequest('/api/signatures/download-audit-trail', [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'doc_id' => $docId,
        ]);
    }

    public function getIdCardIntegrationToken(string $method): array
    {
        return $this->sendRequest('/api/signatures/integration/id-card/get-token', [
            'client_id' => $this->clientId,
            'method' => $method,
        ]);
    }

    public function createSigningQueue(string $docId, array $parameters = []): array
    {
        $data = [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
            'doc_id' => $docId,
        ];

        if (isset($parameters['has_management_page'])) {
            $data['has_management_page'] = $parameters['has_management_page'];
        }
        if (isset($parameters['owner_email'])) {
            $data['owner_email'] = $parameters['owner_email'];
        }
        if (isset($parameters['webhook_url'])) {
            $data['webhook_url'] = $parameters['webhook_url'];
        }
        if (isset($parameters['signers'])) {
            $data['signers'] = $parameters['signers'];
        }

        return $this->sendRequest('/api/signatures/signing-queues', $data);
    }

    protected function sendRequest($path, $body = [], $method = 'POST'): array
    {
        $method = strtoupper($method);
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'nextcloud' => [
                'allow_local_address' => true,
            ],
        ];

        if ($method === 'GET') {
            $options['query'] = $body;
        } else {
            $options['json'] = $body;
        }

        try {
            if ($method === 'POST') {
                $response = $this->getClient()->post($this->apiUrl . $path, $options);
            } elseif ($method === 'GET') {
                $response = $this->getClient()->get($this->apiUrl . $path, $options);
            } else {
                $response = $this->getClient()->request($method, $this->apiUrl . $path, $options);
            }
        } catch (\Throwable $e) {
            try {
                $response = $this->getClient()->getResponseFromThrowable($e);
            } catch (\Throwable $withoutResponse) {
                return [
                    'status' => 'error',
                    'message' => 'No response body: ' . $e->getMessage(),
                ];
            }

            return $this->decodeErrorResponse($response);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function addPrepareFileSigningParams(array $data, array $parameters): array
    {
        if (isset($parameters['visual_coordinates'])) {
            $data['visual_coordinates'] = $parameters['visual_coordinates'];
        }
        if (isset($parameters['signature_redirect'])) {
            $data['signature_redirect'] = $parameters['signature_redirect'];
        }
        if (isset($parameters['nodownload'])) {
            $data['nodownload'] = true;
        }
        if (isset($parameters['noemails'])) {
            $data['noemails'] = true;
        }
        if (isset($parameters['email_extra'])) {
            $data['email_extra'] = $parameters['email_extra'];
        }
        if (isset($parameters['notification_state'])) {
            $data['notification_state'] = $parameters['notification_state'];
        }
        if (isset($parameters['signer'])) {
            $data['signer'] = $parameters['signer'];
        }
        if (isset($parameters['lang'])) {
            $data['lang'] = $parameters['lang'];
        }
        if (isset($parameters['signature_redirect_on_fail'])) {
            $data['signature_redirect_on_fail'] = $parameters['signature_redirect_on_fail'];
        }
        if (isset($parameters['signature_redirect_on_cancel'])) {
            $data['signature_redirect_on_cancel'] = $parameters['signature_redirect_on_cancel'];
        }
        if (isset($parameters['signing_page_url'])) {
            $data['signing_page_url'] = $parameters['signing_page_url'];
        }
        if (isset($parameters['allowed_signature_levels'])) {
            $data['allowed_signature_levels'] = $parameters['allowed_signature_levels'];
        }
        if (isset($parameters['allowed_methods'])) {
            $data['allowed_methods'] = $parameters['allowed_methods'];
        }
        if (isset($parameters['allowed_id_code'])) {
            $data['allowed_id_code'] = $parameters['allowed_id_code'];
        }
        if (isset($parameters['return_available_methods'])) {
            $data['return_available_methods'] = $parameters['return_available_methods'];
        }
        if (isset($parameters['webhook_authorization_bearer_token'])) {
            $data['webhook_authorization_bearer_token'] = $parameters['webhook_authorization_bearer_token'];
        }
        if (isset($parameters['custom_visual_signature'])) {
            $data['custom_visual_signature'] = $parameters['custom_visual_signature'];
        }
        if (isset($parameters['email_extra'])) {
            $data['email_extra'] = $parameters['email_extra'];
        }
        if (isset($parameters['require_signing_reason'])) {
            $data['require_signing_reason'] = $parameters['require_signing_reason'];
        }

        return $data;
    }

    private function getClient(): IClient
    {
        if ($this->client === null) {
            $this->client = $this->clientService->newClient();
        }

        return $this->client;
    }

    private function decodeErrorResponse(IResponse $response): array
    {
        $body = $this->getResponseBody($response);
        $jsonBody = json_decode($body, true);

        if (!$jsonBody) {
            return [
                'status' => 'error',
                'message' => 'Response not json: ' . $body,
            ];
        }

        if (!array_key_exists('status', $jsonBody)) {
            $jsonBody['status'] = 'error';
        }

        return $jsonBody;
    }

    private function decodeJsonResponse(IResponse $response): array
    {
        $decoded = json_decode($this->getResponseBody($response), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function getResponseBody(IResponse $response): string
    {
        $body = $response->getBody();

        if (is_resource($body)) {
            return stream_get_contents($body);
        }

        return (string)$body;
    }
}
