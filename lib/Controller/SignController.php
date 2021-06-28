<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Commands\FetchSignedFile;
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

    /** @var FetchSignedFile */
    private $fetchFileCommand;

	public function __construct($AppName, IRequest $request, GetFileForPreview $getFile, IURLGenerator $urlGenerator, Config $config, FetchSignedFile $fetchSignedFile, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->getFile = $getFile;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->fetchFileCommand = $fetchSignedFile;
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

		list($mimeType, $fileContent, $fileName) = $this->getFile->getOriginalFileData($docId);

		$parameters = [
			'doc_id' => $this->request->getParam('doc_id'),
			'file_mime_type' => $mimeType,
			'file_content' => base64_encode($fileContent),
			'file_url' => $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.sign.downloadFilePreview', ['doc_id' => $docId]),
			'file_name' => $fileName,
			'client_id' => $this->config->getClientId(),
            'api_url' => $this->config->getApiUrl(),
		];

		$response = new TemplateResponse(
			'electronicsignatures', 'signfile', $parameters, 'public'
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain("'self'");
		$csp->addAllowedFrameDomain($this->config->getApiUrl());
		$csp->addAllowedConnectDomain($this->config->getApiUrl());
		$csp->addAllowedObjectDomain("'self'");
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
		list($mimeType, $fileContent, $fileName) = $this->getFile->getOriginalFileData($this->request->getParam('doc_id'));

		return new DataDownloadResponse($fileContent, $fileName, $mimeType);
	}

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function downloadSignedFile(): DataDownloadResponse
    {
        list($mimeType, $fileContent, $fileName) = $this->getFile->getSignedFileData($this->request->getParam('doc_id'));

        return new DataDownloadResponse($fileContent, $fileName, $mimeType);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function showSuccessPage(): TemplateResponse {
        $token = $this->request->getParam('token');

        $this->fetchFileCommand->fetchByToken($token);

        $response = new TemplateResponse(
            'electronicsignatures', 'success', [], 'public'
        );

        return $response;
    }
}
