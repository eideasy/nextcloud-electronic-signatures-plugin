<?php

namespace OCA\ElectronicSignatures\Activity;

use InvalidArgumentException;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;
use OCA\ElectronicSignatures\AppInfo\Application;

class ActivityProvider implements IProvider
{
    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct(IURLGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
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
        $event->setIcon(
            $this->urlGenerator->getAbsoluteURL('/index.php/svg/core/actions/checkmark?color=46BA61')
        );

        return $event;
    }

    private function getSigner(IEvent $event)
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
        return $signer;
    }
}
