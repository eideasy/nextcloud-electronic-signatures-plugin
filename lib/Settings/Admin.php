<?php

namespace OCA\ElectronicSignatures\Settings;

use OCA\Activity\UserSettings;
use OCA\ElectronicSignatures\Config;
use OCP\Activity\IManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Admin implements ISettings {

    /** @var IConfig */
    protected $config;

    /** @var IConfig */
    protected $currentConfig;

    /**
     * @param IConfig $config
     * @param IL10N $l10n
     * @param IManager $manager
     * @param UserSettings $userSettings
     */
    public function __construct(IConfig $config, Config $currentConfig) {
        $this->config = $config;
        $this->currentConfig = $currentConfig;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm() {
        return new TemplateResponse('electronicsignatures', 'settings/admin', [
            'client_id_placeholder' => $this->anoymimize($this->currentConfig->getClientId()),
            'secret_placeholder' => $this->anoymimize($this->currentConfig->getSecret()),
        ], 'blank');
    }

    /**
     * @return string the section ID, e.g. 'sharing'
     */
    public function getSection() {
        return 'electronicsignatures';
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     *
     * E.g.: 70
     */
    public function getPriority() {
        return 55;
    }

    private function anoymimize(string $string): string {
        $letters = str_split($string);
        $lastFour = array_splice($letters, -4);

        $stars = array_fill(0, count($letters), '*');
        $anon = array_merge($stars, $lastFour);
        return implode('', $anon);
    }
}
