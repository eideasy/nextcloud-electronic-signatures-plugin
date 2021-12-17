<?php

namespace OCA\ElectronicSignatures\Commands;

use DateTime;
use DateTimeInterface;
use Exception;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

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
        IRootFolder     $storage,
        IURLGenerator   $urlGenerator,
        SessionMapper   $mapper,
        Config          $config,
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

    /**
     * @throws EidEasyException
     * @throws \OCP\Files\NotFoundException
     * @throws Exception
     */
    public function getSignLink(
        string  $userId,
        string  $path,
        string  $containerType,
        ?string $signerEmails
    )
    {
        list($mimeType, $fileContent, $fileName) = $this->getFile($path, $userId);

        $signatureTime = null;

        // Handle digest based signature starting.
        if ($containerType === "pdf") {
            if (!$this->config->isPadesApiSet()) {
                throw new EidEasyException('Pades URL has not been specified in the settings. If you wish to sign PDFs locally, please set up PADES service (see Settings -> Electronic Signatures for more info)');
            }

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

        $parts = explode('.', $path);
        $extension = strtolower($parts[count($parts) - 1]);
        if ($extension === 'asice') {
            $params['filename'] = basename($path);
            $data = $this->eidEasyApi->prepareAsiceForSigning($fileContent, $params);
        } else {
            $data = $this->eidEasyApi->prepareFiles($sourceFiles, $prepareParams);
        }

        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $this->logger->alert(json_encode($data));
            $message = isset($data['message']) ?
                "eID Easy error: {$data['message']}" :
                'eID Easy error';
            throw new EidEasyException($message);
        }

        $docId = $data['doc_id'];

        $this->saveSession($docId, $path, $userId, $containerType, null, $signerEmails, true, $signatureTime);

        return $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.sign.showSigningPage', ['doc_id' => $docId]);
    }
}
