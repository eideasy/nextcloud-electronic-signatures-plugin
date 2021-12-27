<?php

namespace OCA\ElectronicSignatures\Activity;

use InvalidArgumentException;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCA\ElectronicSignatures\AppInfo\Application;

class ActivityProvider implements IProvider
{
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var IConfig */
    private $iConfig;
    /** @var string */
    private $userId;

    public function __construct(
        IURLGenerator $urlGenerator,
        IConfig $iConfig,
        ?string $userId
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->iConfig = $iConfig;
        $this->userId = $userId;
    }

    /**
     * @param string $language
     * @param IEvent $event
     * @param IEvent|null $previousEvent
     * @return IEvent
     */
    public function parse($language, IEvent $event, IEvent $previousEvent = null): IEvent
    {
        if ($event->getApp() !== Application::APP_ID) {
            throw new InvalidArgumentException();
        }

        $event->setParsedSubject('Signed by ' . $this->getSigner($event));
        $event->setIcon($this->getIconUrl());

        return $event;
    }

    private function getSigner(IEvent $event): string
    {
        $params = $event->getSubjectParameters();

        $signer = '';
        if (isset($params['signer_firstname']) && !empty($params['signer_firstname'])) {
            $signer .= $params['signer_firstname'] . ' ';
        }
        if (isset($params['signer_lastname']) && !empty($params['signer_lastname'])) {
            $signer .= $params['signer_lastname'] . ' ';
        }
        if (isset($params['signer_idcode']) && !empty($params['signer_idcode'])) {
            $signer .= $params['signer_idcode'] . ' ';
        }

        if (isset($otpSigner['email']) && !empty($otpSigner['email'])) {
            return $otpSigner['email'];
        }
        if (isset($otpSigner['phone_number']) && !empty($otpSigner['phone_number'])) {
            return $otpSigner['phone_number'];
        }

        return $signer;
    }

    private function getIconUrl(): string
    {
        $theme = $this->iConfig->getUserValue($this->userId, 'accessibility', 'theme');
        $green = ($theme === 'dark') ? '22751C' : '46BA61';

        return $this->urlGenerator->getAbsoluteURL('/index.php/svg/core/actions/checkmark?color='.$green);
    }
}
