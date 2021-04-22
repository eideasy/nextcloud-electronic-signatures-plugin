<?php

declare(strict_types=1);

namespace OCA\ElectronicSignatures\AppInfo;

use OCP\AppFramework\App;

class Application extends App {

    public function __construct() {
        parent::__construct('electronicsignatures');
        AppShared::registerDependencies();
    }
}
