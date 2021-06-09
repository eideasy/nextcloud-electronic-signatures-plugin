<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Controller;

class FetchSignedFile extends Controller {
    /** @var  IClientService */
    private $httpClientService;

    /** @var IRootFolder */
    private $storage;

    /** @var SessionMapper */
    private $mapper;

    /** @var Config */
    private $config;

    public function __construct(
        IRootFolder $storage,
        IClientService $clientService,
        SessionMapper $mapper,
        Config $config
    ){
        $this->storage = $storage;
        $this->httpClientService = $clientService;
        $this->mapper = $mapper;
        $this->config = $config;
    }

    public function fetch(string $docId): void {
        $session = $this->mapper->findByDocId($docId);

        $responseBody = $this->getContainerResponse($session);

        $this->saveContainerFromResponse($responseBody, $session);
    }

    private function getContainerResponse(Session $session): array {
        // Download signed doc.
        // Send file to eID Easy server.
        $body = [
            'doc_id' => $session->getDocId(),
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
            'lang' => 'en',
        ];

        $client = $this->httpClientService->newClient();
        $response = $client->post($this->config->getUrl('api/signatures/download-signed-asice'), [
            'body' => json_encode($body),
            'headers' => [
                // TODO dynamically get the plugin version and inject to User-Agent.
                'User-Agent' => 'NextCloud-plugin',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function saveContainerFromResponse(array $responseBody, Session $session): void {
        if (!isset($responseBody['signed_file_contents'])) {
            $message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
            throw new EidEasyException($message);
        }

        $userFolder = $this->storage->getUserFolder($session->getUserId());

        $path = $this->getContainerPath($session, $responseBody['filename']);
        $userFolder->touch($path);
        $userFolder->newFile($path, base64_decode($responseBody['signed_file_contents']));
    }

    private function getContainerPath(Session $session, string $containerFilename): string {
        $originalPath = $session->getPath();
        $originalParts = explode('.', $originalPath);

        // Remove file extension.
        array_pop($originalParts);

        $containerParts = explode('.', $containerFilename);

        $beginning = implode('.', $originalParts);
        $extension = $containerParts[count($containerParts) - 1];
        return "$beginning-{$session->getToken()}.$extension";
    }
}
