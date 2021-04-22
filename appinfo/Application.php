<?php

declare(strict_types=1);

namespace OCA\ElectronicSignatures\AppInfo;

use OCP\AppFramework\App;

// Check if class exists, because app.php and Application.php behave unexpectedly in different environments. app.php
// is supposed to be deprecated, but on one of our setups, it was required. On another setup, it did not work.
if (!class_exists(Application::class)) {
    class Application extends App {
        public function __construct() {
            parent::__construct('electronicsignatures');
            AppShared::registerDependencies();
        }
    }
}
