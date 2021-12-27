<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Db\Session;

trait SavesSession
{
    private function saveSession(
        string $docId,
        string $path,
        string $signedPath,
        string $userId,
        string $containerType,
        ?string $token = null,
        ?string $signerEmails = null,
        ?string $currentSignerEmail = null,
        bool $isHashBased = false,
        ?string $signatureTime = null
    ): void
    {
        $token = $token ?: $this->generateRandomString(30);

        // TODO  |  We should actually be getting the file by ID, not by path. Otherwise,
        // TODO  |  if it is moved after signature link is generated, then the
        // TODO  |  container is created in the wrong path.
        $session = new Session();
        $session->setToken($token);
        $session->setDocId($docId);
        $session->setUserId($userId);
        $session->setPath($path);
        $session->setSignedPath($signedPath);
        $session->setIsHashBased((int)$isHashBased);
        $session->setContainerType($containerType);
        if ($signatureTime) {
            $session->setSignatureTime($signatureTime);
        }
        $session->setSignerEmails($signerEmails);
        $session->setCurrentSignerEmail($currentSignerEmail);
        $this->mapper->insert($session);
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
