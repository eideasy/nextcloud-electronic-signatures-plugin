<?php

namespace OCA\ElectronicSignatures\Activity;

use OCA\ElectronicSignatures\AppInfo\Application;
use OCA\ElectronicSignatures\Db\Session;
use OCP\Activity\IManager;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

class ActivityManager
{
    /**
     * @var IManager
     */
    private $manager;
    /**
     * @var IRootFolder
     */
    private $storage;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        IManager        $manager,
        IRootFolder     $storage,
        LoggerInterface $logger
    )
    {
        $this->manager = $manager;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * @param Session $session
     * @param array $fileData
     * @throws \OCP\Files\InvalidPathException
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
     * @throws \OC\User\NoUserException
     */
    public function createAndTriggerEvent(
        Session $session,
        array   $fileData
    ): void
    {
        $author = $session->getUserId();
        $path = $session->getSignedPath();

        $userFolder = $this->storage->getUserFolder($author);
        $file = $userFolder->get($path);
        if (!($file instanceof \OCP\Files\File)) {
            $this->logger->error('Could not create activity entry for path ' . $path . ', author ' . $author . '. Node not found.', ['app' => Application::APP_ID]);
            throw new \OCP\Files\NotFoundException('Can not read from folder');
        }

        $subjectParams = [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'signer_idcode' => $fileData['signer_idcode'],
            'signer_firstname' => $fileData['signer_firstname'],
            'signer_lastname' => $fileData['signer_lastname'],
        ];

        $event = $this->manager->generateEvent();
        $event->setApp(Application::APP_ID)
            ->setType(Application::APP_ID)
            ->setAuthor($author)
            ->setObject('files', $file->getId(), $file->getName())
            ->setSubject('SIGNATURE_ADDED', $subjectParams)
            ->setTimestamp(time())
            ->setAffectedUser($author);
        $this->manager->publish($event);
    }
}
