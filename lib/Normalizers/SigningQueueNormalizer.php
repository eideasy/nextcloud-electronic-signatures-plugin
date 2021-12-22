<?php

namespace OCA\ElectronicSignatures\Normalizers;

use OCP\AppFramework\Db\Entity;

class SigningQueueNormalizer
{
    public const STATUS_DOCUMENT_SIGNED = "document_signed";
    public const STATUS_EMAIL_SENT = "email_sent";
    public const STATUS_EMAIL_PENDING = "email_pending";

    public function normalize(array $sessions)
    {
        $data = ["signersQueue" => []];

        foreach ($sessions as $key => $session) {
            $currentSignerEmail = $session->getCurrentSignerEmail();

            $data["signersQueue"][] = [
                "email" => $currentSignerEmail,
                "status" => $session->getIsDocumentSigned()
                    ? self::STATUS_DOCUMENT_SIGNED
                    : self::STATUS_EMAIL_SENT,
            ];
        }

        $latestSession = $sessions[count($sessions) - 1];
        $signerEmails = $latestSession->getSignerEmails();
        $signerEmailsArray = explode(",", $signerEmails);

        foreach ($signerEmailsArray as $pendingEmails) {
            if (empty($pendingEmails)) {continue;}

            $data["signersQueue"][] = [
                "email" => $pendingEmails,
                "status" => self::STATUS_EMAIL_PENDING,
            ];
        }

        return $data;
    }
}
