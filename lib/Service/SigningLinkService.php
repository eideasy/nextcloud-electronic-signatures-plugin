<?php

namespace OCA\ElectronicSignatures\Service;

use JsonSchema\Exception\ValidationException;
use OCA\ElectronicSignatures\Commands\GetSignLinkLocal;
use OCA\ElectronicSignatures\Commands\GetSignLinkRemote;
use OCA\ElectronicSignatures\Commands\SendSigningLinkToEmail;
use OCA\ElectronicSignatures\Config;
use OCA\Files_External\NotFoundException;
use OCP\Files\IRootFolder;
use OCP\Mail\IMailer;

class SigningLinkService
{
    /** @var IRootFolder */
    private $storage;

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
        IRootFolder            $storage,
        GetSignLinkRemote      $getSignLinkRemote,
        GetSignLinkLocal       $getSignLinkLocal,
        SendSigningLinkToEmail $sendSigningLinkToEmail,
        IMailer                $mailer,
        Config                 $config
    )
    {
        $this->storage = $storage;
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
        string $signedPath,
        string $containerType,
        string $emails
    ): void
    {
        [$email, $nextSignerEmails] = $this->checkForNextEmail($emails);
        if (empty($email)) {
            return;
        }

        if ($this->config->isSigningLocal()) {
            $link = $this->getSignLinkLocalCommand->getSignLink($userId, $path, $signedPath, $containerType, $nextSignerEmails, $email);
        } else {
            $link = $this->getSignLinkRemoteCommand->getSignLink($userId, $path, $signedPath, $containerType, $nextSignerEmails, $email);
        }

        $isAsice = $containerType === Config::CONTAINER_TYPE_ASICE;
        if ($isAsice || !$this->config->isOtpEnabled() || $this->config->isSigningLocal()) {
            $this->sendSigningLinkToEmail->sendEmail($email, $link);
        }
    }

    /**
     * @param string $emails
     * @return array
     */
    private function checkForNextEmail(string $emails): array
    {
        $emails = explode(',', $emails);

        if (!isset($emails[0]) && $emails[0] !== '') {
            $sendMailTo = null;
        } else {
            $sendMailTo = $emails[0];
        }
        array_shift($emails);
        $nextSigners = implode(',', $emails);

        return [$sendMailTo, $nextSigners];
    }

    /**
     * @param array|null $emails
     * @param bool|null $isEmptyAllowed
     * @return string
     */
    public function validateEmails(
        ?array $emails,
        ?bool  $isEmptyAllowed = false
    ): string
    {
        if (empty($emails) && $isEmptyAllowed) {
            return "";
        } else if (empty($emails)) {
            throw new ValidationException("no emails submitted");
        }

        foreach ($emails as $email) {
            if (!$this->mailer->validateMailAddress($email)) {
                throw new ValidationException("no emails submitted");
            }
        }

        return implode(',', $emails);
    }

    public function createFile(
        string $userId,
        string $path,
        string $containerType,
        string $contents
    ): string
    {
        $containerPath = $this->getContainerPath($path, $containerType);
        $this->saveContainer($userId, $contents, $containerPath);

        return $containerPath;
    }


    public function getContainerPath(
        string $originalPath,
        string $containerType
    ): string
    {
        $originalParts = explode('.', $originalPath);

        // Remove file extension.
        array_pop($originalParts);
        $fileName = implode('.', $originalParts);

        // Add date
        if (!str_contains($fileName, '_eidSignedAt-')) {
            $dateTime = (new \DateTime)->format('Ymd-His');
            $fileName = $fileName . '_eidSignedAt-' . $dateTime;
        }

        return $fileName . '.' . $containerType;
    }

    public function saveContainer(
        string $userId,
        string $contents,
        string $containerPath
    ): void
    {
        $userFolder = $this->storage->getUserFolder($userId);

        $userFolder->touch($containerPath);
        $userFolder->newFile($containerPath, $contents);
    }
}
