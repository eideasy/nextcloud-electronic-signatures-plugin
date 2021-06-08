<?php

namespace OCA\ElectronicSignatures\Controller;

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
     */
    public function showSigningPage(): TemplateResponse {
        $parameters = [];

        return new TemplateResponse(
            'electronicsignatures', 'signfile', $parameters, 'blank'
        );
    }
}
