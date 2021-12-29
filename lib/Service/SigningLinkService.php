<?php

namespace OCA\ElectronicSignatures\Service;

use JsonSchema\Exception\ValidationException;
use OCA\ElectronicSignatures\Commands\GetsFile;
use OCA\ElectronicSignatures\Commands\GetSignLinkLocal;
use OCA\ElectronicSignatures\Commands\GetSignLinkRemote;
use OCA\ElectronicSignatures\Commands\SendSigningLinkToEmail;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\Files\IRootFolder;
use OCP\Mail\IMailer;

class SigningLinkService
{
    use GetsFile;

    /** @var IRootFolder */
    private $storage;

    /** @var GetSignLinkRemote */
    private $getSignLinkRemoteCommand;

    /** @var GetSignLinkLocal */
    private $getSignLinkLocalCommand;

    /** @var SendSigningLinkToEmail */
    private $sendSigningLinkToEmail;

    /** @var SessionMapper */
    private $sessionMapper;

    /** @var IMailer */
    private $mailer;

    /** @var Config */
    private $config;

    public function __construct(
        IRootFolder            $storage,
        GetSignLinkRemote      $getSignLinkRemote,
        GetSignLinkLocal       $getSignLinkLocal,
        SendSigningLinkToEmail $sendSigningLinkToEmail,
        SessionMapper          $sessionMapper,
        IMailer                $mailer,
        Config                 $config
    )
    {
        $this->storage = $storage;
        $this->getSignLinkRemoteCommand = $getSignLinkRemote;
        $this->getSignLinkLocalCommand = $getSignLinkLocal;
        $this->sendSigningLinkToEmail = $sendSigningLinkToEmail;
        $this->sessionMapper = $sessionMapper;
        $this->mailer = $mailer;
        $this->config = $config;
    }

    /**
     * @param string $userId
     * @param string $path
     * @param string $signedPath
     * @param string $containerType
     * @param string|null $emails
     * @throws EidEasyException
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\DB\Exception
     * @throws \Exception
     */
    public function sendSignLinkToEmail(
        string  $userId,
        string  $path,
        string  $signedPath,
        string  $containerType,
        ?string $emails
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
            $documentName = $this->getOriginalDocumentName($userId, $signedPath, $path);

            $this->sendSigningLinkToEmail->sendEmail($email, $link, $documentName);
        }
    }

    /**
     * @param string|null $emails
     * @return array
     */
    private function checkForNextEmail(?string $emails): array
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

    /**
     * @param string $userId
     * @param string $path
     * @return array
     * @throws \OCP\Files\NotFoundException
     */
    public function createFileCopy(
        string $userId,
        string $path
    ): array
    {
        $pdfContainerType = $this->config->getContainerType();

        $parts = explode('.', $path);
        $extension = strtolower($parts[count($parts) - 1]);
        $containerType = $extension === Config::CONTAINER_TYPE_PDF ? $pdfContainerType : Config::CONTAINER_TYPE_ASICE;

        list($mimeType, $fileContent) = $this->getFile($path, $userId);
        $signedPath = $this->createFile($userId, $path, $containerType, $fileContent, true);

        return [$signedPath, $containerType];
    }

    public function createFile(
        string $userId,
        string $path,
        string $containerType,
        string $contents,
        bool   $isAddDate = false
    ): string
    {
        $containerPath = $this->getContainerPath($path, $containerType, $isAddDate);
        $this->saveContainer($userId, $contents, $containerPath);

        return $containerPath;
    }

    public function getContainerPath(
        string $originalPath,
        string $containerType,
        bool   $isAddDate
    ): string
    {
        $originalParts = explode('.', $originalPath);

        // Remove file extension.
        array_pop($originalParts);
        $fileName = implode('.', $originalParts);

        // Add date
        if ($isAddDate) {
            $dateTime = (new \DateTime)->format('Ymd-His');
            $fileName = $fileName . '_' . $dateTime;
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

    public function checkCredentials(): void
    {
        if (!$this->config->getClientId() || !$this->config->getSecret()) {
            throw new EidEasyException('Please specify your eID Easy Client ID and secret under Settings -> Electronic Signatures.');
        }
    }

    public function getOriginalDocumentName(
        string $userId,
        string $signedPath,
        string $defaultPath
    ): string
    {
        $sessions = $this->sessionMapper->findBySignedPath($userId, $signedPath);

        $documentPath = isset($sessions[0]) && !empty($sessions[0])
            ? $sessions[0]->getPath()
            : $defaultPath;

        $pathParts = explode("/", $documentPath);

        return $pathParts[array_key_last($pathParts)];
    }
}
