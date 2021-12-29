<?php
namespace OCA\ElectronicSignatures\Db;

use OCP\AppFramework\Db\Entity;

class Session extends Entity {
    protected $token;
    protected $docId;
    protected $userId;
    protected $path;
    protected $sessionPath;
    protected $isHashBased;
    protected $containerType;
    protected $signatureTime;
    protected $isDownloaded;
    protected $signedPath;
    protected $signerEmails;
    protected $emailSent;
    protected $currentSignerEmail;
    protected $isDocumentSigned;

    public function __construct() {
        $this->addType('id','integer');
    }
}
