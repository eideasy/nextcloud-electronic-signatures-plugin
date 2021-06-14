<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Config;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class SettingsApiController extends Controller {
    private $userId;

    /** @var IConfig */
    private $iConfig;

    /** @var Config */
    private $config;

	public function __construct($AppName, IRequest $request, IConfig $iConfig, Config $config, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->iConfig = $iConfig;
		$this->config = $config;
	}

	public function getSettings() {
        return new JSONResponse([
            'enable_otp' => $this->config->isOtpEnabled(),
        ]);
    }

    // TODO remove (deprecated).
    public function updateSettingsDepr() {
	    return $this->updateSettings();
    }

	public function updateSettings() {
	    try {
            // TODO actually check the inputs - attempt to get client config from eID Easy server.
            $clientId = $this->request->getParam('client_id', null);
            if ($clientId !== null) {
                // TODO get app name from some constant, here and elsewhere.
                $this->iConfig->setAppValue('electronicsignatures', 'client_id', $clientId);
            }

            $secret = $this->request->getParam('secret', null);
            if ($secret !== null) {
                $this->iConfig->setAppValue('electronicsignatures', 'secret', $secret);
            }

            $enableOtp = $this->request->getParam('enable_otp', null);
            if ($enableOtp !== null) {
                $this->iConfig->setAppValue('electronicsignatures', 'enable_otp', (int) (bool) $enableOtp);
            }

            $enableLocalSigning = $this->request->getParam('enable_local_signing', null);
            if ($enableLocalSigning !== null) {
                $this->iConfig->setAppValue('electronicsignatures', 'enable_local_signing', (int) (bool) $enableLocalSigning);
            }

            return new JSONResponse(['message' => 'Settings updated!']);
        } catch (\Throwable $e) {
            // TODO log the exception into file.
            return new JSONResponse(['message' => "Failed to update credentials: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
	}
}
