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
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        IRootFolder              $storage,
        SigningLinkService       $signingLinkService,
        RemoteSigningQueueMapper $signingQueueMapper,
        Config                   $config,
        IURLGenerator            $urlGenerator,
        LoggerInterface          $logger
    )
    {
        $this->storage = $storage;
        $this->signingLinkService = $signingLinkService;
        $this->signingQueueMapper = $signingQueueMapper;
        $this->config = $config;
        $this->eidEasyApi = $config->getApi();
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function createSigningQueue(
        string $userId,
        string $path
    ): array
    {
        $token = 'randomstring123';
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
            'signature_redirect' => $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.sign.showSuccessPage', ['token' => $token]),
        ];
        $prepareFilesResponse = $this->eidEasyApi->prepareFiles([$file], $params);
        if ($prepareFilesResponse['status'] !== 'OK') {
            $this->logger->alert(json_encode($prepareFilesResponse));
            $message = $prepareFilesResponse['message']
                ? "eID Easy error: {$prepareFilesResponse['message']}"
                : 'eID Easy error';
            throw new EidEasyException($message);
        }

        $queueResponse = $this->eidEasyApi->createSigningQueue(
            $prepareFilesResponse['doc_id'],
            ['has_management_page' => true]
        );
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
        $queueId,
        $docId
    ): void
    {
        $signingQueue = $this->signingQueueMapper->findByQueueId($queueId);
        if ($signingQueue->getIsDownloaded()) {
            return;
        }

        $userId = $signingQueue->getUserId();
        $path = $signingQueue->getPath(); // what should it be?

        $data = $this->eidEasyApi->downloadSignedFile($docId);
        $signedFileContents = base64_decode($data['signed_file_contents']);

        $this->signingLinkService->createFile(
            $userId,
            $path,
            self::CONTAINER_TYPE,
            $signedFileContents
        );

        $signingQueue->setIsDownloaded(true);
        $this->signingQueueMapper->update($signingQueue);
    }
}
