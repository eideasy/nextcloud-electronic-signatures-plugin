<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Commands\GetFileForPreview;
use OCA\ElectronicSignatures\Config;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IURLGenerator;

class SignController extends OCSController {
	/** @var GetFileForPreview */
	private $getFile;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Config */
	private $config;

	public function __construct($AppName, IRequest $request, GetFileForPreview $getFile, IURLGenerator $urlGenerator, Config $config, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->getFile = $getFile;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
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
		$docId = $this->request->getParam('doc_id');

		list($mimeType, $fileContent, $fileName) = $this->getFile->getFileData($docId);

		$parameters = [
			'doc_id' => $this->request->getParam('doc_id'),
			'file_mime_type' => $mimeType,
			'file_content' => base64_encode($fileContent),
			'file_url' => $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.sign.downloadFilePreview', ['doc_id' => $docId]),
			'file_name' => $fileName,
			'client_id' => $this->config->getClientId(),
		];

		$response = new TemplateResponse(
			'electronicsignatures', 'signfile', $parameters, 'base'
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('https://id.eideasy.com');
		$response->setContentSecurityPolicy($csp);

		$response->addHeader('Referrer-Policy', 'origin');

		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function downloadFilePreview(): DataDownloadResponse
	{
		list($mimeType, $fileContent, $fileName) = $this->getFile->getFileData($this->request->getParam('doc_id'));

		return new DataDownloadResponse($fileContent, $fileName, $mimeType);
	}
}
