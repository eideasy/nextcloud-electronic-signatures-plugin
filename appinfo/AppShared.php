<?php

declare(strict_types=1);

namespace OCA\ElectronicSignatures\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;

class AppShared extends App {
    public static function registerDependencies() {
        Util::addScript('electronicsignatures', '../js/electronic-signatures-main');
        Util::addStyle('electronicsignatures', '../css/icons');
    }
}
