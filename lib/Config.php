<?php

namespace OCA\ElectronicSignatures;

use OCP\IConfig;

class Config {
    private IConfig $config;
    private string $clientId;
    private string $secret;
    private string $baseUrl;

    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    public function getClientId(): string
    {
        if (!$this->clientId) {
            $this->clientId = $this->config->getAppValue('electronicsignatures', 'client_id');
        }

        return $this->clientId;
    }

    public function getSecret(): string
    {
        if (!$this->clientId) {
            $this->secret = $this->config->getAppValue('electronicsignatures', 'secret');
        }

        return $this->secret;
    }

    public function getUrl(string $path): string
    {
        $path = ltrim($path, '/');

        if (!$this->baseUrl) {
            $url = $this->config->getAppValue('electronicsignatures', 'base_url', 'https://id.eideasy.com/api');
            $this->baseUrl = rtrim($url, '/');
        }

        return "$this->baseUrl/$path";
    }
}
