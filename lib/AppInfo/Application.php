<?php

declare(strict_types=1);

namespace OCA\ElectronicSignatures\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Util;

require_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App implements IBootstrap {

    public const APP_ID = 'electronicsignatures';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);

        $container = $this->getContainer();
        $eventDispatcher = $container->get(IEventDispatcher::class);
        $eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function() {
            Util::addScript(self::APP_ID, 'electronic-signatures-fileActions');
            Util::addStyle(self::APP_ID, 'icons');
        });
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
    }
}
