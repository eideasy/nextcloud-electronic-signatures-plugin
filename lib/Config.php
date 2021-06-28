<?php

namespace OCA\ElectronicSignatures;

use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\IConfig;

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Pades;

require_once __DIR__ . '/../vendor/autoload.php';

class Config {
    public const CONTAINER_TYPE_ASICE = 'asice';
    public const CONTAINER_TYPE_PDF = 'pdf';
    public const ENABLE_OTP_BY_DEFAULT = true;
    public const ENABLE_LOCAL_SIGNING_BY_DEFAULT = true;

    private IConfig $config;
    private string $clientId;
    private string $secret;
    private bool $enableOtp;
    private bool $enableLocalSigning;
    private string $baseUrl;
    private EidEasyApi $api;
    private Pades $padesApi;

    public function __construct(IConfig $config, EidEasyApi $api, Pades $padesApi) {
        $this->config = $config;
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

    public function isOtpEnabled(): bool
    {
        if (!isset($this->enableOtp)) {
            $this->enableOtp = (bool) $this->config->getAppValue('electronicsignatures', 'enable_otp', self::ENABLE_OTP_BY_DEFAULT);
        }

        return $this->enableOtp;
    }

    public function isSigningLocal(): bool
    {
        if (!isset($this->enableLocalSigning)) {
            $this->enableLocalSigning = (bool) $this->config->getAppValue('electronicsignatures', 'enable_local_signing', self::ENABLE_LOCAL_SIGNING_BY_DEFAULT);
        }

        return $this->enableLocalSigning;
    }

    public function getApiUrl(string $path = ''): string
    {
        $path = ltrim($path, '/');

        if (!isset($this->baseUrl)) {
            $url = $this->config->getAppValue('electronicsignatures', 'base_url', 'https://id.eideasy.com');
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
