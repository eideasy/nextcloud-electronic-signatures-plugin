<?php

namespace OCA\ElectronicSignatures;

use OCP\IConfig;

class Config {
    public const CONTAINER_TYPE = 'asice';

    private IConfig $config;
    private string $clientId;
    private string $secret;
    private bool $enableOtp;
    private string $baseUrl;

    public function __construct(IConfig $config) {
        $this->config = $config;
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

    public function isOtpEnabled(): string
    {
        if (!isset($this->enableOtp)) {
            $this->enableOtp = (bool) $this->config->getAppValue('electronicsignatures', 'enable_otp', false);
        }

        return $this->enableOtp;
    }

    public function getUrl(string $path): string
    {
        $path = ltrim($path, '/');

        if (!isset($this->baseUrl)) {
            $url = $this->config->getAppValue('electronicsignatures', 'base_url', 'https://id.eideasy.com');
            $this->baseUrl = rtrim($url, '/');
        }

        return "$this->baseUrl/$path";
    }
}
