<?php

namespace OCA\ElectronicSignatures\Controller;

use EidEasy\Signatures\Pades;
use Exception;
use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Commands\GetSignLinkLocal;
use OCA\ElectronicSignatures\Commands\GetSignLinkRemote;
use OCA\ElectronicSignatures\Commands\SendSigningLinkToEmail;
use OCA\ElectronicSignatures\Config;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;

class SignApiController extends OCSController
{
    private $userId;

    /** @var IMailer */
    private $mailer;

    /** @var GetSignLinkRemote */
    private $getSignLinkRemoteCommand;

    /** @var GetSignLinkLocal */
    private $getSignLinkLocalCommand;

    /** @var FetchSignedFile */
    private $fetchFileCommand;

    /** @var SendSigningLinkToEmail */
    private $sendSigningLinkToEmail;

    /** @var Config */
    private $config;

    /** @var Pades */
    private $pades;

    public function __construct(
        $AppName,
        IRequest $request,
        Imailer $mailer,
        GetSignLinkRemote $getSignLinkRemote,
        GetSignLinkLocal $getSignLinkLocal,
        SendSigningLinkToEmail $sendSigningLinkToEmail,
        FetchSignedFile $fetchSignedFile,
        Config $config,
        LoggerInterface $logger,
        Pades $pades,
        $UserId
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->mailer = $mailer;
        $this->getSignLinkRemoteCommand = $getSignLinkRemote;
        $this->getSignLinkLocalCommand = $getSignLinkLocal;
        $this->fetchFileCommand = $fetchSignedFile;
        $this->sendSigningLinkToEmail = $sendSigningLinkToEmail;
        $this->config = $config;
        $this->logger = $logger;
        $this->pades = $pades;
    }

    /**
     * @NoAdminRequired
     */
    public function sendSignLinkByEmail()
    {
        try {
            $path = $this->request->getParam('path');
            $email = $this->request->getParam('email');
            $containerType = $this->getContainerType($path);

            if (!$this->mailer->validateMailAddress($email)) {
                return new JSONResponse([
                    'message' => 'Provided email address is not valid',
                ], Http::STATUS_BAD_REQUEST);
            }

            $link = $this->getSignLink($path, $containerType, $email);

            $this->sendSigningLinkToEmail->sendIfNecessary($containerType, $email, $link);

            return new JSONResponse(['message' => 'E-mail sent!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to send email: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
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
            $this->logger->alert('fetch ' . json_encode($this->request->getParams()));
            $docId = $this->request->getParam('doc_id');

            $this->fetchFileCommand->fetch($docId);

            return new JSONResponse(['message' => 'Fetched successfully!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
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

    private function getSignLink(string $path, string $containerType, ?string $email = null): string
    {
        $this->logger->alert('get signing link ' . json_encode([$path, $containerType, $email]));
        if ($this->config->isSigningLocal()) {
            return $this->getSignLinkLocalCommand->getSignLink($this->userId, $path, $containerType);
        } else {
            return $this->getSignLinkRemoteCommand->getSignLink($this->userId, $path, $containerType, $email);
        }
    }
}
