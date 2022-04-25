<?php
namespace OCA\ElectronicSignatures\Db;

use OCP\AppFramework\Db\Entity;

class RemoteSigningQueue extends Entity {
    protected $signingQueueId;
    protected $signingQueueSecret;
    protected $userId;
    protected $originalFilePath;
    protected $isDownloaded;

    public function __construct() {
        $this->addType('id','integer');
    }
}
