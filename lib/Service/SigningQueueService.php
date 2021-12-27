<?php

namespace OCA\ElectronicSignatures\Service;

use OCA\ElectronicSignatures\Db\SessionMapper;
use OCP\AppFramework\Db\Entity;

class SigningQueueService
{
    public const STATUS_DOCUMENT_SIGNED = "document_signed";
    public const STATUS_EMAIL_SENT = "email_sent";
    public const STATUS_EMAIL_PENDING = "email_pending";

    /** @var SigningLinkService */
    private $signingLinkService;
    /** @var SessionMapper */
    private $sessionMapper;

    public function __construct(
        SigningLinkService $signingLinkService,
        SessionMapper      $sessionMapper
    )
    {
        $this->signingLinkService = $signingLinkService;
        $this->sessionMapper = $sessionMapper;
    }

    public function createSigningQueue(
        string $userId,
        string $path,
        ?array $emailsInput
    )
    {
        $this->signingLinkService->checkCredentials();

        $emails = $this->signingLinkService->validateEmails($emailsInput);

        [
            $signedPath,
            $containerType
        ] = $this->signingLinkService->createFileCopy($userId, $path);

        $this->signingLinkService->sendSignLinkToEmail($userId, $path, $signedPath, $containerType, $emails);
    }

    /**
     * @param string $userId
     * @param string $path
     * @param array $emailsInput
     * @return array
     * @throws \OCA\ElectronicSignatures\Exceptions\EidEasyException
     * @throws \OCP\DB\Exception
     * @throws \OCP\Files\NotFoundException
     */
    public function updateSigningQueue(
        string $userId,
        string $path,
        array  $emailsInput
    ): array
    {
        $emails = $this->signingLinkService->validateEmails($emailsInput, true);

        $sessions = $this->sessionMapper->findBySignedPath($userId, $path);
        if (empty($sessions)) {
            return [];
        }

        $latestSession = $this->getLatestSession($sessions);
        $this->updateQueue($latestSession, $emails, $userId);

        return $this->getQueueByPath($userId, $path);
    }

    /**
     * @throws \OCP\DB\Exception
     */
    public function getQueueByPath(
        string $userId,
        string $path
    ): array
    {
        $sessions = $this->sessionMapper->findBySignedPath($userId, $path);

        if (empty($sessions)) {
            return [];
        }

        return $this->getQueueFromSession($sessions);
    }

    /**
     * @param Entity $latestSession
     * @param string $emails
     * @param string $userId
     * @throws \OCA\ElectronicSignatures\Exceptions\EidEasyException
     * @throws \OCP\DB\Exception
     * @throws \OCP\Files\NotFoundException
     */
    private function updateQueue(
        Entity $latestSession,
        string $emails,
        string $userId
    ): void
    {
        // If not able to update, new email is needed to send out
        if (!$this->isAbleToUpdateQueue($latestSession)) {
            $this->signingLinkService->sendSignLinkToEmail(
                $userId,
                $latestSession->getSignedPath(),
                $latestSession->getSignedPath(),
                $latestSession->getContainerType(),
                $emails
            );
            return;
        }

        $latestSession->setSignerEmails($emails);
        $this->sessionMapper->update($latestSession);
    }

    /**
     * @param array $sessions
     * @return array
     */
    public function getQueueFromSession(array $sessions): array
    {
        $data = ["signersQueue" => []];

        $data = $this->addDocumentSentAndEmailSent($data, $sessions);
        $data = $this->addEmailPending($data, $sessions);

        return $data;
    }

    private function addDocumentSentAndEmailSent(array $data, array $sessions): array
    {
        foreach ($sessions as $session) {
            $data["signersQueue"][] = [
                "email" => $session->getCurrentSignerEmail(),
                "status" => $session->getIsDocumentSigned()
                    ? self::STATUS_DOCUMENT_SIGNED
                    : self::STATUS_EMAIL_SENT,
            ];
        }

        return $data;
    }

    private function addEmailPending(array $data, array $sessions): array
    {
        $latestSession = $this->getLatestSession($sessions);
        $signerEmails = $latestSession->getSignerEmails();
        $signerEmailsArray = explode(",", $signerEmails);

        foreach ($signerEmailsArray as $pendingEmails) {
            if (empty($pendingEmails)) {
                continue;
            }

            $data["signersQueue"][] = [
                "email" => $pendingEmails,
                "status" => self::STATUS_EMAIL_PENDING,
            ];
        }

        return $data;
    }

    private function getLatestSession(array $sessions): Entity
    {
        $lastSessionIndex = count($sessions) - 1;

        return $sessions[$lastSessionIndex];
    }

    private function isAbleToUpdateQueue(Entity $latestSession): bool
    {
        $signerEmails = $latestSession->getSignerEmails();
        $signerEmailsArray = explode(",", $signerEmails);
        $isSigningQueueEmpty = !isset($signerEmailsArray[0]) || empty($signerEmailsArray[0]);

        $isDocumentSigned = $latestSession->getIsDocumentSigned();

        return !$isSigningQueueEmpty || !$isDocumentSigned;
    }
}
