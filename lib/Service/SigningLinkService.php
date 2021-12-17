<?php

namespace OCA\ElectronicSignatures\Service;

use JsonSchema\Exception\ValidationException;
use OCA\ElectronicSignatures\Commands\GetSignLinkLocal;
use OCA\ElectronicSignatures\Commands\GetSignLinkRemote;
use OCA\ElectronicSignatures\Commands\SendSigningLinkToEmail;
use OCA\ElectronicSignatures\Config;
use OCA\Files_External\NotFoundException;
use OCP\Mail\IMailer;

class SigningLinkService
{
    /** @var GetSignLinkRemote */
    private $getSignLinkRemoteCommand;

    /** @var GetSignLinkLocal */
    private $getSignLinkLocalCommand;

    /** @var SendSigningLinkToEmail */
    private $sendSigningLinkToEmail;

    /** @var IMailer */
    private $mailer;

    /** @var Config */
    private $config;

    public function __construct(
        GetSignLinkRemote      $getSignLinkRemote,
        GetSignLinkLocal       $getSignLinkLocal,
        SendSigningLinkToEmail $sendSigningLinkToEmail,
        IMailer                $mailer,
        Config                 $config
    )
    {
        $this->getSignLinkRemoteCommand = $getSignLinkRemote;
        $this->getSignLinkLocalCommand = $getSignLinkLocal;
        $this->sendSigningLinkToEmail = $sendSigningLinkToEmail;
        $this->mailer = $mailer;
        $this->config = $config;
    }

    /**
     * @param string $userId
     * @param string $path
     * @param string $containerType
     * @param string $emails
     * @throws NotFoundException
     * @throws \OCA\ElectronicSignatures\Exceptions\EidEasyException
     * @throws \OCP\DB\Exception
     * @throws \OCP\Files\NotFoundException
     * @throws \Exception
     */
    public function sendSignLinkToEmail(
        string $userId,
        string $path,
        string $containerType,
        string $emails
    ): void
    {
        [$email, $nextSignerEmails] = $this->checkForNextEmail($emails);

        if ($this->config->isSigningLocal()) {
            $link = $this->getSignLinkLocalCommand->getSignLink($userId, $path, $containerType, $nextSignerEmails);
        } else {
            $link = $this->getSignLinkRemoteCommand->getSignLink($userId, $path, $containerType, $nextSignerEmails, $email);
        }

        $this->sendSigningLinkToEmail->sendIfNecessary($containerType, $email, $link);
    }

    /**
     * @throws NotFoundException|\OCP\DB\Exception
     */
    private function checkForNextEmail(string $emails): array
    {
        $emails = explode(',', $emails);

        if (!isset($emails[0]) && $emails[0] !== '') {
            throw new NotFoundException('All have signed');
        }
        $sendMailTo = $emails[0];
        array_shift($emails);
        $nextSigners = implode(',', $emails);

        return [$sendMailTo, $nextSigners];
    }

    /**
     * @param array|null $emails
     * @return string
     */
    public function validateEmails(?array $emails): string
    {
        if (empty($emails)) {
            throw new ValidationException("no emails submitted");
        }

        foreach ($emails as $email) {
            if (!$this->mailer->validateMailAddress($email)) {
                throw new ValidationException("no emails submitted");
            }
        }

        return implode(',', $emails);
    }
}
