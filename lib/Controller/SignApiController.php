<?php

namespace OCA\ElectronicSignatures\Controller;

use Exception;
use JsonSchema\Exception\ValidationException;
use OC\User\NoUserException;
use OCA\ElectronicSignatures\AppInfo\Application;
use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCA\ElectronicSignatures\Service\SigningLinkService;
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

    /** @var SigningLinkService */
    private $signingLinkService;

    /** @var IConfig */
    private $iConfig;

    /** @var Config */
    private $config;

    public function __construct(
        $AppName,
        IRequest $request,
        FetchSignedFile $fetchSignedFile,
        SigningLinkService $signingLinkService,
        Pades $pades,
        LoggerInterface $logger,
        IConfig $iConfig,
        Config $config,
        $UserId
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->fetchFileCommand = $fetchSignedFile;
        $this->signingLinkService = $signingLinkService;
        $this->iConfig = $iConfig;
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
            $containerType = $this->iConfig->getAppValue(Application::APP_ID, 'container_type');
            $emailsInput = $this->request->getParam('emails');
            $emails = $this->signingLinkService->validateEmails($emailsInput);

            $this->signingLinkService->sendSignLinkToEmail($this->userId, $path, $containerType, $emails);
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
            $session = $this->fetchFileCommand->fetchByDocId($docId);

            $path = $session->getSignedPath();
            $emails = $session->getSignerEmails();
            $containerType = $session->getContainerType();
            $userId = $session->getUserId();

            $this->signingLinkService->sendSignLinkToEmail($userId, $path, $containerType, $emails);
        } catch (NoUserException | NotPermittedException | NotPermittedException | InvalidPathException $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            throw new EidEasyException('Failed to create event for activity.' . $e->getMessage() . ' trace ' . $e->getTraceAsString());
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new JSONResponse(['message' => 'Fetched successfully!']);
    }

    private function getContainerType(string $path)
    {
        $containerType = $this->request->getParam('container_type', Config::CONTAINER_TYPE_ASICE);

        $parts = explode('.', $path);
        $extension = strtolower($parts[count($parts) - 1]);

        // If file is not pdf, but container type is, then throw exception.
        if ($extension !== 'pdf' && $containerType === Config::CONTAINER_TYPE_PDF) {
            throw new Exception('Container type is PDF, but file type is not.');
        }

        // If container type is not recognized, then throw exception.
        if (!in_array($containerType, [Config::CONTAINER_TYPE_PDF, Config::CONTAINER_TYPE_ASICE])) {
            throw new Exception('Unknown container type.');
        }

        return $containerType;
    }

    private function checkCredentials(): void
    {
        if (!$this->config->getClientId() || !$this->config->getSecret()) {
            throw new EidEasyException('Please specify your eID Easy Client ID and secret under Settings -> Electronic Signatures.');
        }
    }
}
