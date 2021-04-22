<?php

namespace OCA\ElectronicSignatures\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class SettingsApiController extends Controller {
    private $userId;

    /** @var IConfig */
    private $config;

	public function __construct($AppName, IRequest $request, IConfig $config, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->config = $config;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoCSRFRequired
	 * TODO re-enable CSRF check?
	 */
	public function updateCredentials() {
	    // TODO get app name from some constant, here and elsewhere.
        $clientId = $this->request->getParam('clientId', null);
        $secret = $this->request->getParam('secret', null);

        // TODO actually check the inputs - attempt to get client config from eID Easy server.
        if ($clientId !== null) {
            $this->config->setAppValue('electronicsignatures', 'client_id', $clientId);
        }

        if ($secret !== null) {
            $this->config->setAppValue('electronicsignatures', 'secret', $secret);
        }

		return new JSONResponse(['hi' => 'done']);
	}
}
