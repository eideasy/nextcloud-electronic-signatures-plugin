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

	public function updateCredentials() {
        try {
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

            return new JSONResponse(['message' => 'eID Easy credentials updated!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
	}
}
