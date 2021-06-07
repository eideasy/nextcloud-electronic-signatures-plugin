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

    public function getSignLink(string $userId, string $path, string $email): string {
        // TODO mark base64 ext as dependency in composer.json.
        list($mimeType, $contents) = $this->getFile($path, $userId);
        $base64 = base64_encode($contents);

        $token = $this->generateRandomString(30);

        $responseBody = $this->startSigningSession($path, $base64, $mimeType, $email);

        if (!isset($responseBody['doc_id'])) {
            $message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
            throw new EidEasyException($message);
        }

        // Return eID Easy server link
        $docId = $responseBody['doc_id'];

        $this->saveSession($token, $docId, $path, $userId);

        return $this->config->getUrl("/sign_contract_external?client_id={$this->config->getClientId()}&doc_id=$docId&lang=en");
    }

    private function getFile(string $path, $userId): array {
        $userFolder = $this->storage->getUserFolder($userId);

        try {
            $file = $userFolder->get($path);

            if ($file instanceof \OCP\Files\File) {
                return [$file->getMimeType(), $file->getContent()];
            } else {
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

    private function startSigningSession(string $path, string $fileContentBase64, string $mimeType, string $email): array {
        // Send file to eID Easy server.
        $body = [
            'files' => [
                [
                    'fileName' => basename($path),
                    'fileContent' => $fileContentBase64,
                    'mimeType' => $mimeType,
                ],
            ],
            'container_type' => Config::CONTAINER_TYPE,
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
            'lang' => 'en',
        ];


        if ($this->config->isOtpEnabled()) {
            $body['signer'] = [
                'send_now' => true,
                'contacts' => [
                    [
                        'type' => 'email',
                        'value' => $email,
                    ]
                ],
            ];
        }


        $client = $this->httpClientService->newClient();
        $config = [
            'body' => json_encode($body),
            'headers' => [
                // TODO dynamically get the plugin version and inject to User-Agent.
                'User-Agent' => 'NextCloud-plugin',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
        ];
        $response = $client->post($this->config->getUrl('api/signatures/prepare-files-for-signing'), $config);
        $responseBody = json_decode($response->getBody(), true);

        if ($response->getStatusCode() !== 200) {
            $message = $responseBody['message'] ? "eID Easy error: {$responseBody['message']}" : 'eID Easy error';
            throw new EidEasyException($message);
        }

        // TODO mark json ext as dependency in composer.json.
        return $responseBody;
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
