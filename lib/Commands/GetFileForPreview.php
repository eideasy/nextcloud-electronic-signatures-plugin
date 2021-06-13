<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Db\SessionMapper;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;

class GetFileForPreview extends Controller
{
    use GetsFile;

    private $userId;

	/** @var IRootFolder */
	private $storage;

    /** @var SessionMapper */
    private $mapper;

    public function __construct(IRootFolder $storage, SessionMapper $mapper)
    {
		$this->storage = $storage;
    	$this->mapper = $mapper;
    }

    public function getFileData(string $docId): array
    {
        $session = $this->mapper->findByDocId($docId);
    	return $this->getFile($session->getPath(), $session->getUserId());
    }
}
