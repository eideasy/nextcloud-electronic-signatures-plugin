<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Commands\GetFileForPreview;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class SignController extends OCSController {
	/** @var GetFileForPreview */
	private $getFile;

	public function __construct($AppName, IRequest $request, GetFileForPreview $getFile, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->getFile = $getFile;
	}

    /**
     * @PublicPage
     * @NoCSRFRequired
	 * @deprecated TODO remove.
     */
    public function showSigningPageDepr(): TemplateResponse {
        return $this->showSigningPage();
    }

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function showSigningPage(): TemplateResponse {
		list($mimeType, $fileContent, $fileName) = $this->getFile->getFileData($this->request->getParam('doc_id'));
		$parameters = [
			'doc_id' => $this->request->getParam('doc_id'),
			'file_mime_type' => $mimeType,
			'file_content' => base64_encode($fileContent),
			'file_name' => $fileName,
		];

		$response = new TemplateResponse(
			'electronicsignatures', 'signfile', $parameters, 'base'
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('https://id.eideasy.com');
		$csp->addAllowedConnectDomain('https://id.eideasy.com');
		$response->setContentSecurityPolicy($csp);

		$response->addHeader('Referrer-Policy', 'origin');

		return $response;
	}
}
