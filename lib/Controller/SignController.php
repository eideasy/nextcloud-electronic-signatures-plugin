<?php

namespace OCA\ElectronicSignatures\Controller;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class SignController extends OCSController {
	public function __construct($AppName, IRequest $request, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
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
		$parameters = [];

		$response = new TemplateResponse(
			'electronicsignatures', 'signfile', $parameters, 'base'
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain('https://id.eideasy.com');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
