<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

use EidEasy\Api\EidEasyApi;

class GetSignLinkRemote extends Controller
{
    use GetsFile;
    use SavesSession;

    private $userId;

    /** @var IRootFolder */
    private $storage;

    /** @var SessionMapper */
    private $mapper;

    /** @var LoggerInterface */
    private $logger;

    /** @var Config */
    private $config;

    /** @var EidEasyApi */
    private $eidEasyApi;

    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct(IRootFolder $storage, SessionMapper $mapper, LoggerInterface $logger, Config $config, IURLGenerator $urlGenerator, $UserId)
    {
        $this->userId = $UserId;
        $this->storage = $storage;
        $this->mapper = $mapper;
        $this->logger = $logger;
        $this->config = $config;
        $this->eidEasyApi = $config->getApi();
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @throws EidEasyException
     * @throws \OCP\Files\NotFoundException
     */
    public function getSignLink(
        string $userId,
        string $path,
        string $signedPath,
        string $containerType,
        string $signerEmails,
        string $email,
        string $apiLang
    ): string
    {
        list($mimeType, $contents) = $this->getFile($path, $userId);
        $base64 = base64_encode($contents);

        $token = $this->generateRandomString(30);

        $responseBody = $this->startSigningSession($path, $base64, $mimeType, $email, $containerType, $token, $apiLang);

        if (!isset($responseBody['doc_id'])) {
            $this->logger->alert(json_encode($responseBody));
            $message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
            throw new EidEasyException($message);
        }

        // Return eID Easy server link
        $docId = $responseBody['doc_id'];

        $this->saveSession($docId, $path, $signedPath, $userId, $containerType, $token, $signerEmails, $email);

        return $this->config->getApiUrl("/sign_contract_external?client_id={$this->config->getClientId()}&doc_id=$docId&lang=en");
    }

    private function startSigningSession(
        string $path,
        string $fileContentBase64,
        string $mimeType,
        string $email,
        string $containerType,
        string $token,
        string $apiLang
    ): array
    {
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
            'lang' => $apiLang,
            'signature_redirect' => $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.sign.showSuccessPage', ['token' => $token]),
        ];

        $isAsice = $containerType === Config::CONTAINER_TYPE_ASICE;
        if ($this->config->isOtpEnabled() && !$isAsice) {
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

        $parts = explode('.', $path);
        $extension = strtolower($parts[count($parts) - 1]);
        $isAddSignature = $containerType === Config::CONTAINER_TYPE_ASICE && $extension === Config::CONTAINER_TYPE_ASICE;

        if ($isAddSignature) {
            $params['filename'] = basename($path);
            $responseBody = $this->eidEasyApi->prepareAsiceForSigning($fileContentBase64, $params);
        } else {
            $responseBody = $this->eidEasyApi->prepareFiles($files, $params);
        }

        if ($responseBody['status'] !== 'OK') {
            $this->logger->alert(json_encode($responseBody));
            $message = $responseBody['message'] ? "eID Easy error: {$responseBody['message']}" : 'eID Easy error';
            throw new EidEasyException($message);
        }

        return $responseBody;
    }
}
