<?php

namespace OCA\ElectronicSignatures\Service;

use OCA\ElectronicSignatures\Signature\SignatureParameters;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;

class PadesClient
{
    /** @var string */
    private $apiUrl;

    /** @var IClientService */
    private $clientService;

    /** @var IClient|null */
    private $client;

    public function __construct(IClientService $clientService)
    {
        $this->clientService = $clientService;
        $this->apiUrl = 'https://detached-pdf.eideasy.com';
    }

    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }

    public function addSignaturePades(string $pdfFile, string $signatureTime, string $cadesSignature, ?array $padesDssData, ?SignatureParameters $parameters = null): array
    {
        $data = [
            'fileContent' => base64_encode($pdfFile),
            'signatureTime' => $signatureTime,
            'signatureValue' => $cadesSignature,
        ];

        if ($parameters) {
            $data['reason'] = $parameters->getReason();
            $data['contactInfo'] = $parameters->getContactInfo();
            $data['location'] = $parameters->getLocation();
            $data['signerName'] = $parameters->getSignerName();
        }

        if ($padesDssData) {
            $data['padesDssData'] = $padesDssData;
        }

        return $this->sendRequest('/api/detached-pades/complete', $data);
    }

    public function getPadesDigest(string $pdfFile, ?SignatureParameters $parameters = null): array
    {
        $data = [
            'fileContent' => base64_encode($pdfFile),
        ];

        if ($parameters) {
            $data['reason'] = $parameters->getReason();
            $data['contactInfo'] = $parameters->getContactInfo();
            $data['location'] = $parameters->getLocation();
            $data['signerName'] = $parameters->getSignerName();
        }

        return $this->sendRequest('/api/detached-pades/prepare', $data);
    }

    protected function sendRequest($path, $body): array
    {
        try {
            $response = $this->getClient()->post($this->apiUrl . $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => $body,
                'nextcloud' => [
                    'allow_local_address' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            try {
                $response = $this->getClient()->getResponseFromThrowable($e);
            } catch (\Throwable $withoutResponse) {
                return [
                    'status' => 'error',
                    'message' => 'No response body: ' . $e->getMessage(),
                ];
            }

            $body = $this->getResponseBody($response);
            $jsonBody = json_decode($body);
            if (!$jsonBody) {
                return [
                    'status' => 'error',
                    'message' => 'Response not json: ' . $body,
                ];
            }

            return [
                'status' => 'error',
                'message' => $body,
            ];
        }

        $decoded = json_decode($this->getResponseBody($response), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function getClient(): IClient
    {
        if ($this->client === null) {
            $this->client = $this->clientService->newClient();
        }

        return $this->client;
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
