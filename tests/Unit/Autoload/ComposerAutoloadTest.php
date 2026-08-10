<?php

namespace OCA\ElectronicSignatures\Tests\Unit\Autoload;

use PHPUnit\Framework\TestCase;

class ComposerAutoloadTest extends TestCase
{
    public function testAppComposerAutoloadDoesNotRegisterGuzzle(): void
    {
        $appRoot = dirname(__DIR__, 3);
        $autoloadPsr4 = $appRoot . '/vendor/composer/autoload_psr4.php';

        if (file_exists($autoloadPsr4)) {
            $prefixes = require $autoloadPsr4;

            $this->assertArrayNotHasKey('GuzzleHttp\\', $prefixes);
            $this->assertArrayNotHasKey('GuzzleHttp\\Promise\\', $prefixes);
            $this->assertArrayNotHasKey('GuzzleHttp\\Psr7\\', $prefixes);
        } else {
            $this->assertTrue(true, 'The app has no Composer autoloader in production packages.');
        }

        $this->assertFalse(is_dir($appRoot . '/vendor/guzzlehttp/guzzle'));
    }

    public function testGuzzleProxyDoesNotComeFromAppVendor(): void
    {
        $appRoot = dirname(__DIR__, 3);
        $autoload = $appRoot . '/vendor/autoload.php';

        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists('GuzzleHttp\\Handler\\Proxy')) {
            $this->assertTrue(true, 'Guzzle Proxy is not available after loading the app autoloader.');
            return;
        }

        $reflection = new \ReflectionClass('GuzzleHttp\\Handler\\Proxy');
        $filename = str_replace('\\', '/', (string)$reflection->getFileName());

        $this->assertStringNotContainsString('/apps/electronicsignatures/vendor/guzzlehttp/guzzle/', $filename);
    }
}
