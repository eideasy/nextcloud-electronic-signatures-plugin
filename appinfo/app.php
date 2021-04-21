<?php

declare(strict_types=1);

namespace OCA\ElectronicSignatures\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

    public function __construct() {
        parent::__construct('electronicsignatures');
        Util::addScript('electronicsignatures', '../js/electronic-signatures-main');
        Util::addStyle('electronicsignatures', '../css/icons');
    }
}
