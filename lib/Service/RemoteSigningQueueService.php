<?php

namespace OCA\ElectronicSignatures\Service;

use EidEasy\Api\EidEasyApi;
use OCA\ElectronicSignatures\Commands\GetsFile;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\RemoteSigningQueue;
use OCA\ElectronicSignatures\Db\RemoteSigningQueueMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

class RemoteSigningQueueService
{
    use GetsFile;

    private const CONTAINER_TYPE = 'pdf';

    /** @var IRootFolder */
    private $storage;
    /** @var SigningLinkService */
    private $signingLinkService;
    /** @var RemoteSigningQueueMapper */
    private $signingQueueMapper;
    /** @var Config */
    private $config;
    /** @var EidEasyApi */
    private $eidEasyApi;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IRootFolder              $storage,
        SigningLinkService       $signingLinkService,
        RemoteSigningQueueMapper $signingQueueMapper,
        Config                   $config,
        LoggerInterface          $logger
    )
    {
        $this->storage = $storage;
        $this->signingLinkService = $signingLinkService;
        $this->signingQueueMapper = $signingQueueMapper;
        $this->config = $config;
        $this->eidEasyApi = $config->getApi();
        $this->logger = $logger;
    }

    public function createSigningQueue(
        string $userId,
        string $path
    ): array
    {
        $docId = $this->getFileAndPrepare($path, $userId);

        return $this->createAndSaveQueue($docId, $userId, $path);
    }

    protected function getFileAndPrepare(
        string $path,
        string $userId
    )
    {
        list($mimeType, $contents) = $this->getFile($path, $userId);

        $file = [
            'fileName' => basename($path),
            'fileContent' => base64_encode($contents),
            'mimeType' => $mimeType,
        ];
        $params = [
            'container_type' => 'pdf',
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
//            'lang' => $apiLang,
        ];
        $prepareFilesResponse = $this->eidEasyApi->prepareFiles([$file], $params);
        if ($prepareFilesResponse['status'] !== 'OK') {
            $this->logger->alert(json_encode($prepareFilesResponse));
            $message = $prepareFilesResponse['message']
                ? "eID Easy error: {$prepareFilesResponse['message']}"
                : 'eID Easy error';
            throw new EidEasyException($message);
        }
        return $prepareFilesResponse['doc_id'];
    }

    protected function createAndSaveQueue(
        string $docId,
        string $userId,
        string $path
    ): array
    {
        $queueResponse = $this->eidEasyApi->createSigningQueue($docId, [
            'has_management_page' => true,
        ]);
        if (!isset($queueResponse['id'], $queueResponse['signing_queue_secret'])) {
            $this->logger->alert(json_encode($queueResponse));
            throw new EidEasyException('eID Easy error');
        }

        $signingQueue = new RemoteSigningQueue();
        $signingQueue->setSigningQueueId($queueResponse['id']);
        $signingQueue->setSigningQueueSecret($queueResponse['signing_queue_secret']);
        $signingQueue->setUserId($userId);
        $signingQueue->setOriginalFilePath($path);
        $this->signingQueueMapper->insert($signingQueue);

        return $queueResponse;
    }

    public function fetchSignedFile(
        string $queueId,
        string $docId
    ): void
    {
        $signingQueue = $this->signingQueueMapper->findByQueueId($queueId);
        if ($signingQueue->getIsDownloaded()) {
            return;
        }

        $userId = $signingQueue->getUserId();
        $path = $signingQueue->getOriginalFilePath();

        $data = $this->eidEasyApi->downloadSignedFile($docId);
        $signedFileContents = base64_decode($data['signed_file_contents']);

        $this->signingLinkService->createFile(
            $userId,
            $path,
            self::CONTAINER_TYPE,
            $signedFileContents,
            true
        );

        $signingQueue->setIsDownloaded(true);
        $this->signingQueueMapper->update($signingQueue);
    }
}
