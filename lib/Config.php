<?php

namespace OCA\ElectronicSignatures;

use OCP\IConfig;
use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;
use OCP\IURLGenerator;

require_once __DIR__ . '/../vendor/autoload.php';

class Config {
    public const PROD_URL = 'https://id.eideasy.com';
    public const SANDBOX_URL = 'https://test.eideasy.com';
    public const CONTAINER_TYPE_ASICE = 'asice';
    public const CONTAINER_TYPE_PDF = 'pdf';
    public const ENABLE_OTP_BY_DEFAULT = true;
    public const SIGNING_MODE_REMOTE_BY_DEFAULT = 'remote';
    public const SIGNING_MODE_LOCAL = 'local';

    /** @var IConfig */
    private $config;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var string */
    private $clientId;

    /** @var string */
    private $secret;

    /** @var string */
    private $containerType;

    /** @var bool */
    private $enableSandbox;

    /** @var bool */
    private $enableOtp;

    /** @var bool */
    private $enableLocalSigning;

    /** @var string */
    private $baseUrl;

    /** @var EidEasyApi */
    private $api;

    /** @var string|null */
    private $apiLanguage;

    /** @var Pades */
    private $padesApi;

    /** @var string */
    private $signingMode;

    /** @var string|null */
    private $remoteSigningQueueWebhook;

    /** @var string|null */
    private $defaultRemoteSigningQueueWebhook;

    public function __construct(IConfig $config, IURLGenerator $urlGenerator, EidEasyApi $api, Pades $padesApi) {
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->initApi($api);
        $this->initPadesApi($padesApi);
    }

    public function getClientId(): string
    {
        if (!isset($this->clientId)) {
            $this->clientId = $this->config->getAppValue('electronicsignatures', 'client_id');
        }

        return $this->clientId;
    }

    public function getSecret(): string
    {
        if (!isset($this->secret)) {
            $this->secret = $this->config->getAppValue('electronicsignatures', 'secret');
        }

        return $this->secret;
    }

    public function getContainerType(): string
    {
        if (!isset($this->containerType)) {
            $this->containerType = $this->config->getAppValue('electronicsignatures', 'container_type', self::CONTAINER_TYPE_PDF);
        }

        return $this->containerType;
    }

    public function getApiLanguage(): ?string
    {
        if (!isset($this->apiLanguage)) {
            $this->apiLanguage = $this->config->getAppValue('electronicsignatures', 'api_language', null);
        }

        return $this->apiLanguage;
    }

    public function isSandboxEnabled(): bool
    {
        if (!isset($this->enableSandbox)) {
            $this->enableSandbox = (bool) $this->config->getAppValue('electronicsignatures', 'enable_sandbox', false);
        }

        return $this->enableSandbox;
    }

    public function isOtpEnabled(): bool
    {
        if (!isset($this->enableOtp)) {
            $this->enableOtp = (bool) $this->config->getAppValue('electronicsignatures', 'enable_otp', self::ENABLE_OTP_BY_DEFAULT);
        }

        return $this->enableOtp;
    }

    public function isSigningLocal(): bool
    {
        return $this->getSigningMode() === self::SIGNING_MODE_LOCAL;
    }

    public function getSigningMode(): string
    {
        if (!isset($this->signingMode)) {
            $this->signingMode = $this->config->getAppValue('electronicsignatures', 'signing_mode', self::SIGNING_MODE_REMOTE_BY_DEFAULT);
        }

        return $this->signingMode;
    }

    public function getDefaultRemoteSigningQueueWebhook(): ?string
    {
        if (!isset($this->defaultRemoteSigningQueueWebhook)) {
            $this->defaultRemoteSigningQueueWebhook = $this->urlGenerator->linkToRouteAbsolute('electronicsignatures.remoteQueueApi.fetchSigningQueueFile');
        }

        return $this->defaultRemoteSigningQueueWebhook;
    }

    public function getRemoteSigningQueueWebhook(): ?string
    {
        if (!isset($this->remoteSigningQueueWebhook)) {
            $this->remoteSigningQueueWebhook = $this->config->getAppValue('electronicsignatures', 'remote_signing_queue_status_webhook', null);
        }

        return $this->remoteSigningQueueWebhook;
    }

    public function getApiUrl(string $path = ''): string
    {
        $path = ltrim($path, '/');

        if (!isset($this->baseUrl)) {
            $url = $this->config->getAppValue(
                'electronicsignatures',
                'base_url',
                $this->isSandboxEnabled() ? self::SANDBOX_URL : self::PROD_URL
            );
            $this->baseUrl = rtrim($url, '/');
        }

        if (!$path) {
            return $this->baseUrl;
        }

        return "$this->baseUrl/$path";
    }

	public function getPadesApiUrl(): string
	{
		if (!isset($this->padesBaseUrl)) {
			$url = $this->config->getAppValue('electronicsignatures', 'pades_url');
			$this->padesBaseUrl = rtrim($url, '/');
		}

        return $this->padesBaseUrl;
	}

    public function getApi(): EidEasyApi
    {
        return $this->api;
    }

	public function getPadesApi(): Pades
	{
		return $this->padesApi;
	}

    public function isPadesApiSet(): bool
    {
        return !!$this->getPadesApiUrl();
    }

    private function initApi(EidEasyApi $api): void
    {
        $this->api = $api;
        $this->api->setApiUrl($this->getApiUrl());
        $this->api->setClientId($this->getClientId());
        $this->api->setSecret($this->getSecret());
    }

	private function initPadesApi(Pades $padesApi): void
	{
		$this->padesApi = $padesApi;
		$this->padesApi->setApiUrl($this->getPadesApiUrl());
	}
}
