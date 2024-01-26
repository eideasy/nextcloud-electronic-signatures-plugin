<?php

namespace OCA\ElectronicSignatures\Service;

use EidEasy\Api\EidEasyApi;
use OCA\ElectronicSignatures\Commands\GetsFile;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\RemoteSigningQueue;
use OCA\ElectronicSignatures\Db\RemoteSigningQueueMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class RemoteSigningQueueService
{
    use GetsFile;

    public const DEFAULT_API_LANGUAGE = 'en';

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
    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct(
        IRootFolder              $storage,
        SigningLinkService       $signingLinkService,
        RemoteSigningQueueMapper $signingQueueMapper,
        Config                   $config,
        LoggerInterface          $logger,
        IURLGenerator            $urlGenerator
    )
    {
        $this->storage = $storage;
        $this->signingLinkService = $signingLinkService;
        $this->signingQueueMapper = $signingQueueMapper;
        $this->config = $config;
        $this->eidEasyApi = $config->getApi();
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
    }

    public function createSigningQueue(
        string $userId,
        string $path
    ): array
    {
        if (!$this->config->getClientId() || !$this->config->getSecret()) {
            throw new EidEasyException('Please specify your eID Easy Client ID and secret under Settings -> Electronic Signatures.');
        }

        $docId = $this->getFileAndPrepare($path, $userId);

        return $this->createAndSaveQueue($docId, $userId, $path);
    }

    protected function getFileAndPrepare(
        string $path,
        string $userId
    )
    {
        list($mimeType, $contents, $fileName, $extension) = $this->getFile($path, $userId);

        $file = [
            'fileName' => $fileName,
            'fileContent' => base64_encode($contents),
            'mimeType' => $mimeType,
        ];
        $params = [
            'container_type' => $this->config->getContainerType(),
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
            'lang' => $this->config->getApiLanguage() ?? self::DEFAULT_API_LANGUAGE,
        ];

        if ($extension === 'asice') {
            $prepareFilesResponse = $this->eidEasyApi->prepareAsiceForSigning($file['fileContent'], $file);
        } else {
            $prepareFilesResponse = $this->eidEasyApi->prepareFiles([$file], $params);
        }
        if ($prepareFilesResponse['status'] !== 'OK') {
            $this->logger->alert(json_encode($prepareFilesResponse));
            $message = 'File preparation failed.';
            if ($prepareFilesResponse['message']) {
                $message = $message . ' Cause: ' . $prepareFilesResponse['message'];
            }
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
        $webhookUrl = $this->config->getRemoteSigningQueueWebhook();
        if (empty($webhookUrl)) {
            $webhookUrl= $this->config->getDefaultRemoteSigningQueueWebhook();
        }

        $this->logger->info("Remote queue status webhook url: $webhookUrl");

        $queueResponse = $this->eidEasyApi->createSigningQueue($docId, [
            'has_management_page' => true,
            'webhook_url' => $webhookUrl
        ]);
        if (!isset($queueResponse['id'], $queueResponse['signing_queue_secret'])) {
            $this->logger->alert(json_encode($queueResponse));
            throw new EidEasyException('Failed to create remote signing queue');
        }

        $signingQueue = new RemoteSigningQueue();
        $signingQueue->setSigningQueueId($queueResponse['id']);
        $signingQueue->setSigningQueueSecret($queueResponse['signing_queue_secret']);
        $signingQueue->setUserId($userId);
        $signingQueue->setOriginalFilePath($path);
        $this->signingQueueMapper->insert($signingQueue);

        return [
            'management_page_url' => $queueResponse['management_page_url']
        ];
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
        $filenameParts = explode('.', $data['filename']);
        $containerType = $filenameParts[array_key_last($filenameParts)];

        $this->signingLinkService->createFile(
            $userId,
            $path,
            $containerType,
            $signedFileContents,
            true
        );

        $signingQueue->setIsDownloaded(true);
        $this->signingQueueMapper->update($signingQueue);
    }
}
