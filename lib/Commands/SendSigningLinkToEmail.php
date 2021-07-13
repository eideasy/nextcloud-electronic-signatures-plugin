<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Config;
use OCP\AppFramework\Controller;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Util;

class SendSigningLinkToEmail extends Controller {
    private $userId;

    /** @var IMailer */
    private $mailer;

    /** @var IUserSession */
    private $session;

    /** @var Config */
    private $config;

    public function __construct(IMailer $mailer, IUserSession $userSession, Config $config, $UserId) {
        $this->userId = $UserId;
        $this->mailer = $mailer;
        $this->session = $userSession;
        $this->config = $config;
    }

    public function sendIfNecessary(string $containerType, string $email, string $link): void {
        // We do not need to send the e-mail if OTP is enabled, because in
        // this case, eID Easy will be sending the e-mail instead of us.
        if ($containerType === Config::CONTAINER_TYPE_PDF && $this->config->isOtpEnabled()) {
            return;
        }

        $user = $this->session->getUser();

        if (!$user->getDisplayName()) {
            throw new \Exception('Your profile e-mail display name must be set.');
        }

        $emailTemplate = $this->mailer->createEMailTemplate('calendar.PublicShareNotification', [
            'link' => $link,
        ]);

        $emailTemplate->setSubject('A document awaits your signature');

        $emailTemplate->addHeader();
        $emailTemplate->addHeading("{$user->getDisplayName()} has sent you a document for signing.");
        $emailTemplate->addBodyText('By clicking the button below, you can review it and sign it.');
        $emailTemplate->addBodyButton('Review document', $link);
        $emailTemplate->addFooter();

        $message = $this->createMessage([$email => $email], $emailTemplate, $user);

        try {
            $this->mailer->send($message);
        } catch (\Swift_TransportException $exception) {
            throw new \Exception($exception->getMessage() . '. Did you set up your email server correctly?');
        }
    }

    /**
     * @param string $from
     * @param array $recipients
     * @param IEMailTemplate $template
     * @return IMessage
     */
    private function createMessage(
        array $recipients,
        IEMailTemplate $template,
        IUser $user
    ): IMessage {
        $message = $this->mailer->createMessage();
        $message->setFrom([Util::getDefaultEmailAddress('no-reply') => $user->getDisplayName()]);
        $message->setTo($recipients);
        $message->useTemplate($template);

        return $message;
    }
}
