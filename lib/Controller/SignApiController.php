<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Commands\GetSignLink;
use OCA\ElectronicSignatures\Commands\SendSigningLinkToEmail;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Mail\IMailer;

class SignApiController extends OCSController {
    private $userId;

    /** @var IMailer */
    private $mailer;

    /** @var GetSignLink */
    private $getSignLinkCommand;

    /** @var FetchSignedFile */
    private $fetchFileCommand;

    /** @var SendSigningLinkToEmail */
    private $sendSigningLinkToEmail;

	public function __construct($AppName, IRequest $request, Imailer $mailer, GetSignLink $getSignLink, SendSigningLinkToEmail $sendSigningLinkToEmail, FetchSignedFile $fetchSignedFile, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->mailer = $mailer;
		$this->getSignLinkCommand = $getSignLink;
		$this->fetchFileCommand = $fetchSignedFile;
		$this->sendSigningLinkToEmail = $sendSigningLinkToEmail;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getSignLink() {
        try {
            $path = $this->request->getParam('path');

            $link = $this->getSignLinkCommand->getSignLink($this->userId, $path);

            return new JSONResponse(['sign_link' => $link]);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
	}

    /**
     * @NoAdminRequired
     */
    public function sendSignLinkByEmail() {
        try {
            $path = $this->request->getParam('path');
            // TODO Validate e-mail.
            $email = $this->request->getParam('email');

            if (!$this->mailer->validateMailAddress($email)) {
                return new JSONResponse([
                    'message' => 'Provided email-address is not valid',
                ], Http::STATUS_BAD_REQUEST);
            }

            $link = $this->getSignLinkCommand->getSignLink($this->userId, $path);

            $this->sendSigningLinkToEmail->send($email, $link);

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
    public function fetchSignedFile() {
        try {
            $docId = $this->request->getParam('doc_id');

            $this->fetchFileCommand->fetch($docId);

            return new JSONResponse(['message' => 'Fetched successfully!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
