<?php

declare(strict_types=1);

use OCP\Util;

if (!function_exists('electronicsignatures_register_dependencies')) {
    function electronicsignatures_register_dependencies() {
        Util::addScript('electronicsignatures', '../js/electronic-signatures-main');
        Util::addStyle('electronicsignatures', '../css/icons');
    }
}
