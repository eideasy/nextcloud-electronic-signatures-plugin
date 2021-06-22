<?php

namespace OCA\ElectronicSignatures\Commands;

use DateTime;
use DateTimeInterface;
use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class GetSignLinkLocal extends Controller
{
    use GetsFile;
    use SavesSession;

    private $userId;

    /** @var IRootFolder */
    private $storage;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var SessionMapper */
    private $mapper;

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    /** @var EidEasyApi */
    private $eidEasyApi;

    /** @var Pades */
    private $padesApi;

    public function __construct(
        IRootFolder $storage,
        IURLGenerator $urlGenerator,
        SessionMapper $mapper,
        Config $config,
        LoggerInterface $logger,
        $UserId
    )
    {
        $this->userId = $UserId;
        $this->storage = $storage;
        $this->urlGenerator = $urlGenerator;
        $this->mapper = $mapper;
        $this->config = $config;
        $this->padesApi = $config->getPadesApi();
        $this->eidEasyApi = $config->getApi();
        $this->logger = $logger;
    }

    public function getSignLink(string $userId, string $path, string $containerType)
    {
        list($mimeType, $fileContent, $fileName) = $this->getFile($path, $userId);

        $signatureTime = null;

        // Handle digest based signature starting.
        $signatureContainer = $containerType;
        if ($containerType === "pdf") {
            $padesResponse = $this->padesApi->getPadesDigest($fileContent);
            if (!isset($padesResponse['digest'])) {
                throw new EidEasyException('Pades preparation failed.');
            }
            $fileContent = $padesResponse['digest']; // Modified PDF digest will be signed.

            $signatureTime = $padesResponse['signatureTime'];

            $signatureContainer = 'cades';
        } elseif ($containerType === 'asice') {
            $fileContent = base64_encode(hash('sha256', $fileContent, true));

            $signatureContainer = 'xades';
        } else {
            // Throw this because otherwise the non-hashed file is sent to remote server, betraying the user's expectations.
            throw new Exception('Unknown container type.');
        }

        $this->logger->alert('basinga  ' . json_encode(strlen($fileContent)));
        $this->logger->alert('conttype ' . $containerType);
        $sourceFiles = [
            [
                'fileName' => $fileName,
                'mimeType' => $mimeType,
                'fileContent' => $fileContent,
            ]
        ];

        $prepareParams = [
            'container_type' => $signatureContainer,
            'baseline' => 'LT',
            'notification_state' => [
                'time' => (new DateTime())->format(DateTimeInterface::ISO8601),
            ],
        ];

        $data = $this->eidEasyApi->prepareFiles($sourceFiles, $prepareParams);

        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $this->logger->alert(json_encode($data));
            $message = isset($data['message']) ?
                "eID Easy error: {$data['message']}" :
                'eID Easy error';
            throw new EidEasyException($message);
        }

        $docId = $data['doc_id'];

        $this->saveSession($docId, $path, $userId, $containerType, true, $signatureTime);

        return $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.sign.showSigningPage', ['doc_id' => $docId]);
    }
}