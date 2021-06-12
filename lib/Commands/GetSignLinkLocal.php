<?php

namespace OCA\ElectronicSignatures\Commands;

use DateTime;
use DateTimeInterface;
use EidEasy\Api\EidEasyApi;
use Exception;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Controller;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class GetSignLinkLocal extends Controller
{
    use GetsFile;
    use SavesSession;

    private $userId;

    /** @var  IClientService */
    private $httpClientService;

    /** @var IRootFolder */
    private $storage;

    /** @var ISecureRandom */
    private $secureRandom;

    /** @var SessionMapper */
    private $mapper;

    /** @var Config */
    private $config;

    /** @var EidEasyApi */
    private $eidEasyApi;

    public function __construct(IRootFolder $storage, IClientService $clientService, ISecureRandom $secureRandom, SessionMapper $mapper, Config $config, LoggerInterface $logger, $UserId)
    {
        $this->userId = $UserId;
        $this->storage = $storage;
        $this->secureRandom = $secureRandom;
        $this->mapper = $mapper;
        $this->httpClientService = $clientService;
        $this->config = $config;
        $this->eidEasyApi = $config->getApi();
        // TODO remove logger.
        $this->logger = $logger;

    }

    public function getSignLink(string $userId, string $path, string $containerType)
    {
        list($mimeType, $fileContent, $fileName) = $this->getFile($path, $userId);

        // Handle digest based signature starting.
        $signatureContainer = $containerType;
        if ($containerType === "pdf") {
            // TODO implement PDF digest signing.
            throw new Exception('PDF local signing is not yet implemented');
        } elseif ($containerType === 'asice') {
            $signatureContainer = 'xades';
        }
        $this->logger->alert('TESTx 1');

        $sourceFiles = [
            [
                'fileName' => $fileName,
                'mimeType' => $mimeType,
                'fileContent' => base64_encode(hash('sha256', $fileContent, true)),
            ]
        ];

        $prepareParams = [
            'container_type' => $signatureContainer,
            'baseline' => 'LT',
            'notification_state' => [
                'time' => (new DateTime())->format(DateTimeInterface::ISO8601),
            ],
        ];
        $this->logger->alert('TESTx 2');

        $data = $this->eidEasyApi->prepareFiles($sourceFiles, $prepareParams);

        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $message = isset($data['message']) ?
                "eID Easy error: {$data['message']}" :
                'eID Easy error';
            throw new EidEasyException($message);
        }
        $this->logger->alert('TESTx 3');

        $docId = $data['doc_id'];

        $this->saveSession($docId, $path, $userId, $containerType, true);
        $this->logger->alert('TESTx 4');

        // TODO return link.
        return 'https://example.com/';
    }
}
