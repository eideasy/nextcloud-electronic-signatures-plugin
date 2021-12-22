<?php

namespace OCA\ElectronicSignatures\Controller;

use JsonSchema\Exception\ValidationException;
use OC\User\NoUserException;
use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCA\ElectronicSignatures\Normalizers\SigningQueueNormalizer;
use OCA\ElectronicSignatures\Service\SigningLinkService;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\InvalidPathException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

use EidEasy\Signatures\Pades;

class SignApiController extends OCSController
{
    private $userId;

    /** @var FetchSignedFile */
    private $fetchFileCommand;

    /** @var Pades */
    private $pades;

    /** @var LoggerInterface */
    private $logger;

    /** @var SessionMapper */
    private $sessionMapper;

    /** @var SigningLinkService */
    private $signingLinkService;

    /** @var SigningQueueNormalizer */
    private $signingQueueNormalizer;

    /** @var Config */
    private $config;

    public function __construct(
        $AppName,
        IRequest $request,
        FetchSignedFile $fetchSignedFile,
        SigningLinkService $signingLinkService,
        Pades $pades,
        LoggerInterface $logger,
        SessionMapper $sessionMapper,
        SigningQueueNormalizer $signingQueueNormalizer,
        Config $config,
        $UserId
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->fetchFileCommand = $fetchSignedFile;
        $this->signingLinkService = $signingLinkService;
        $this->sessionMapper = $sessionMapper;
        $this->signingQueueNormalizer = $signingQueueNormalizer;
        $this->config = $config;
        $this->pades = $pades;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     */
    public function sendSignLinkByEmail()
    {
        try {
            $this->checkCredentials();

            $path = $this->request->getParam('path');
            $pdfContainerType = $this->config->getContainerType();
            $emailsInput = $this->request->getParam('emails');
            $emails = $this->signingLinkService->validateEmails($emailsInput);

            $parts = explode('.', $path);
            $extension = strtolower($parts[count($parts) - 1]);
            $containerType = $extension === Config::CONTAINER_TYPE_PDF ? $pdfContainerType : Config::CONTAINER_TYPE_ASICE;

            $signedPath = $this->signingLinkService->createFile($this->userId, $path, $containerType, '', true);
            $this->signingLinkService->sendSignLinkToEmail($this->userId, $path, $signedPath, $containerType, $emails);
        } catch (ValidationException $e) {
            return new JSONResponse([
                'message' => 'Provided email address is not valid',
            ], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse([
                'message' => "Failed to send email: {$e->getMessage()}"
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new JSONResponse(['message' => 'E-mail sent!']);
    }

    /**
     * eID Easy server will call this in the background when user signs document.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function fetchSignedFile()
    {
        try {
            $docId = $this->request->getParam('doc_id');

            $this->fetchFileCommand->fetchByDocId($docId);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new JSONResponse(['message' => 'Fetched successfully!']);
    }

    private function checkCredentials(): void
    {
        if (!$this->config->getClientId() || !$this->config->getSecret()) {
            throw new EidEasyException('Please specify your eID Easy Client ID and secret under Settings -> Electronic Signatures.');
        }
    }

    public function getSigningQueue()
    {
        try {
            $path = $this->request->getParam('path');
            $sessions = $this->sessionMapper->findByPath($this->userId, $path);

            if (empty($sessions)) {
                return new JSONResponse([]);
            }
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to fetch signing queue: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new JSONResponse($this->signingQueueNormalizer->normalize($sessions));
    }

    public function updateSigningQueue()
    {
        try {
            $path = $this->request->getParam('path');
            $emailsInput = $this->request->getParam('emails');
            $emails = $this->signingLinkService->validateEmails($emailsInput, true);

            $sessions = $this->sessionMapper->findByPath($this->userId, $path);

            if (empty($sessions)) {
                return new JSONResponse([]);
            }

            /** @var Entity $latestSession */
            $latestSession = $sessions[count($sessions) - 1];
            $latestSession->setSignerEmails($emails);
            $this->sessionMapper->update($latestSession);

            $sessions = $this->sessionMapper->findByPath($this->userId, $path);
        } catch (ValidationException $e) {
            return new JSONResponse(['message' => 'Provided email address is not valid',], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to update signing queue: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new JSONResponse($this->signingQueueNormalizer->normalize($sessions));
    }
}
