<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Commands\GetSignLink;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class SignApiController extends OCSController {
    private $userId;

    /** @var GetSignLink */
    private $getSignLinkCommand;

    /** @var FetchSignedFile */
    private $fetchFileCommand;

	public function __construct($AppName, IRequest $request, GetSignLink $getSignLink, FetchSignedFile $fetchSignedFile, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->getSignLinkCommand = $getSignLink;
		$this->fetchFileCommand = $fetchSignedFile;
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
     * eID Easy server will call this in the background when user signs document.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function fetchSignedFile() {
        try {
            $docId = $path = $this->request->getParam('doc_id');

            $this->fetchFileCommand->fetch($docId);

            return new JSONResponse(['message' => 'Fetched successfully!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
