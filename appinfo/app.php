<?php

declare(strict_types=1);

namespace OCA\ElectronicSignatures\AppInfo;

use OCP\AppFramework\App;

require_once dirname(__FILE__) . '/register-dependencies.php';

// For explanation, refer to comments in Application.php
if (!class_exists(Application::class)) {
    class Application extends App {
        public function __construct() {
            parent::__construct('electronicsignatures');
            electronicsignatures_register_dependencies();
        }
    }
}
