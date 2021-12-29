<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Config;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Util;

class SendSigningLinkToEmail
{
    /** @var IMailer */
    private $mailer;

    /** @var IUserSession */
    private $session;

    /** @var Config */
    private $config;

    public function __construct(IMailer $mailer, IUserSession $userSession, Config $config)
    {
        $this->mailer = $mailer;
        $this->session = $userSession;
        $this->config = $config;
    }

    /**
     * @param string $email
     * @param string $link
     * @throws \Exception
     */
    public function sendEmail(
        string $email,
        string $link,
        string $documentName
    ): void
    {
        $emailTemplate = $this->mailer->createEMailTemplate('calendar.PublicShareNotification', [
            'link' => $link,
        ]);

        $emailTemplate->setSubject('Please sign "' . $documentName . '"');

        $emailTemplate->addHeader();
        $emailTemplate->addHeading('You have been sent "' . $documentName . '" for signing.');
        $emailTemplate->addBodyText('By clicking the button below, you can review it and sign it.');
        $emailTemplate->addBodyButton('Review document', $link);
        $emailTemplate->addFooter();

        $message = $this->createMessage([$email => $email], $emailTemplate);

        try {
            $this->mailer->send($message);
        } catch (\Swift_TransportException $exception) {
            throw new \Exception($exception->getMessage() . '. Did you set up your email server correctly?');
        }
    }

    /**
     * @param array $recipients
     * @param IEMailTemplate $template
     * @return IMessage
     */
    private function createMessage(
        array          $recipients,
        IEMailTemplate $template
    ): IMessage
    {
        $message = $this->mailer->createMessage();
        $message->setTo($recipients);
        $message->useTemplate($template);

        return $message;
    }
}
