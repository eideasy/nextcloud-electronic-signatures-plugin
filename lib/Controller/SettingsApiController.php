<?php

namespace OCA\ElectronicSignatures\Controller;

use OCP\AppFramework\Http;
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

	public function updateSettings() {
	    try {
            // TODO actually check the inputs - attempt to get client config from eID Easy server.
            $clientId = $this->request->getParam('client_id', null);
            if ($clientId !== null) {
                // TODO get app name from some constant, here and elsewhere.
                $this->config->setAppValue('electronicsignatures', 'client_id', $clientId);
            }

            $secret = $this->request->getParam('secret', null);
            if ($secret !== null) {
                $this->config->setAppValue('electronicsignatures', 'secret', $secret);
            }

            $enableOtp = $this->request->getParam('enable_otp', null);
            if ($enableOtp !== null) {
                $this->config->setAppValue('electronicsignatures', 'enable_otp', (int) (bool) $enableOtp);
            }

            return new JSONResponse(['message' => 'Settings updated!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to update credentials: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
	}
}
