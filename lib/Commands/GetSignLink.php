<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Controller;

class GetSignLink extends Controller {
    private $userId;

    /** @var  IClientService */
    private $httpClientService;

    /** @var IRootFolder */
    private $storage;

    /** @var SessionMapper */
    private $mapper;

    /** @var Config */
    private $config;

    public function __construct(IRootFolder $storage, IClientService $clientService, SessionMapper $mapper, Config $config, $UserId) {
        $this->userId = $UserId;
        $this->storage = $storage;
        $this->mapper = $mapper;
        $this->httpClientService = $clientService;
        $this->config = $config;
    }

    public function getSignLink(string $userId, string $path): string {
        // TODO mark base64 ext as dependency in composer.json.
        $base64 = base64_encode($this->getFileContents($path, $userId));

        $token = $this->generateRandomString(30);

        $responseBody = $this->startSigningSession($path, $base64, $token);

        if (!isset($responseBody['doc_id'])) {
            $message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
            throw new EidEasyException($message);
        }

        // Return eID Easy server link
        $docId = $responseBody['doc_id'];

        $this->saveSession($token, $docId, $path, $userId);

        return $this->config->getUrl("/sign_contract_external?client_id={$this->config->getClientId()}&doc_id=$docId");
    }

    private function getFileContents(string $path, $userId): string {
        $userFolder = $this->storage->getUserFolder($userId);

        try {
            $file = $userFolder->get($path);

            if ($file instanceof \OCP\Files\File) {
                return $file->getContent();
            } else {
                // TODO test that when Accepts header is application/json, a decent error structure is returned.
                throw new NotFoundException('Can not read from folder');
            }
        } catch (\OCP\Files\NotFoundException $e) {
            throw new NotFoundException('File does not exist');
        }
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function startSigningSession(string $path, string $fileContentBase64, string $token): array {
        // Send file to eID Easy server.
        $body = [
            'files' => [
                [
                    'fileName' => basename($path),
                    'fileContent' => $fileContentBase64,
                    'mimeType' => 'text/plain',
                ],
            ],
            'container_type' => Config::CONTAINER_TYPE,
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
        ];

        $client = $this->httpClientService->newClient();
        $response = $client->post($this->config->getUrl('api/signatures/prepare-files-for-signing'), [
            'body' => json_encode($body),
            'headers' => [
                // TODO dynamically get the plugin version and inject to User-Agent.
                'User-Agent' => 'NextCloud-plugin',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        // TODO mark json ext as dependency in composer.json.
        return json_decode($response->getBody(), true);
    }

    private function saveSession(string $token, string $docId, string $path, string $userId): void {
        // TODO  |  We should actually be getting the file by ID, not by path. Otherwise,
        // TODO  |  if it is moved after signature link is generated, then the
        // TODO  |  container is created in the wrong path.
        $session = new Session();
        $session->setToken($token);
        $session->setDocId($docId);
        $session->setUserId($userId);
        $session->setPath($path);
        $session->setUsed(0);
        $this->mapper->insert($session);
    }
}
