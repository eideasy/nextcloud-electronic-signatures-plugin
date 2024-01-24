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

class Application extends App implements IBootstrap {

    public const APP_ID = 'electronicsignatures';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);

        $container = $this->getContainer();
        $eventDispatcher = $container->get(IEventDispatcher::class);
        $eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function() {
            Util::addInitScript(self::APP_ID, 'electronic-signatures-fileActions');
        });
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
    }
}
