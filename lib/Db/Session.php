<?php
namespace OCA\ElectronicSignatures\Db;

use OCP\AppFramework\Db\Entity;

class Session extends Entity {
    protected $token;
    protected $docId;
    protected $userId;
    protected $path;
    protected $used;

    public function __construct() {
        $this->addType('id','integer');
    }
}
