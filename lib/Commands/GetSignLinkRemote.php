<?php

namespace OCA\ElectronicSignatures\Commands;

use EidEasy\Api\EidEasyApi;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\AppFramework\Controller;
use Psr\Log\LoggerInterface;

class GetSignLinkRemote extends Controller {
    use GetsFile;
    use SavesSession;

    private $userId;

    /** @var IRootFolder */
    private $storage;

    /** @var SessionMapper */
    private $mapper;

    /** @var Config */
    private $config;

    /** @var EidEasyApi */
    private $eidEasyApi;

    public function __construct(IRootFolder $storage, SessionMapper $mapper, Config $config, LoggerInterface $logger, $UserId) {
        $this->userId = $UserId;
        $this->storage = $storage;
        $this->mapper = $mapper;
        $this->config = $config;
        $this->logger = $logger;
        $this->eidEasyApi = $config->getApi();
    }

    public function getSignLink(string $userId, string $path, string $containerType, string $email): string {
        list($mimeType, $contents) = $this->getFile($path, $userId);
        $base64 = base64_encode($contents);

        $responseBody = $this->startSigningSession($path, $base64, $mimeType, $email, $containerType);

        if (!isset($responseBody['doc_id'])) {
            $message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
            throw new EidEasyException($message);
        }

        // Return eID Easy server link
        $docId = $responseBody['doc_id'];

        $this->saveSession($docId, $path, $userId, $containerType);

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

    private function startSigningSession(string $path, string $fileContentBase64, string $mimeType, string $email, string $containerType): array {
        // Send file to eID Easy server.
        $files = [
            [
                'fileName' => basename($path),
                'fileContent' => $fileContentBase64,
                'mimeType' => $mimeType,
            ],
        ];

        $params = [
            'container_type' => $containerType,
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
            'lang' => 'en',
        ];


        if ($this->config->isOtpEnabled()) {
			$params['signer'] = [
                'send_now' => true,
                'contacts' => [
                    [
                        'type' => 'email',
                        'value' => $email,
                    ]
                ],
            ];
        }

        $responseBody = $this->eidEasyApi->prepareFiles($files, $params);

        if ($responseBody['status'] !== 'OK') {
            $message = $responseBody['message'] ? "eID Easy error: {$responseBody['message']}" : 'eID Easy error';
            throw new EidEasyException($message);
        }

        return $responseBody;
    }
}
