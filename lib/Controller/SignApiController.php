<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Commands\GetSignLink;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class SignApiController extends Controller {
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
        $path = $this->request->getParam('path');

        $link = $this->getSignLinkCommand->getSignLink($this->userId, $path);

		return new JSONResponse(['sign_link' => $link]);
	}

    /**
     * eID Easy server will call this in the background when user signs document.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function fetchSignedFile() {
        $docId = $path = $this->request->getParam('doc_id');

        $this->fetchFileCommand->fetch($docId);

        return new JSONResponse(['message' => 'Fetched successfully!']);
    }
}
