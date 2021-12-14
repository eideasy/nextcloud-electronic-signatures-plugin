<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Asice;
use EidEasy\Signatures\Pades;

class FetchSignedFile extends Controller
{
    use GetsFile;

    /** @var IRootFolder */
    private $storage;

    /** @var SessionMapper */
    private $mapper;

    /** @var EidEasyApi */
    private $eidEasyApi;

    /** @var Pades */
    private $padesApi;

    public function __construct(
        IRootFolder $storage,
        SessionMapper $mapper,
        Config $config
    )
    {
        $this->storage = $storage;
        $this->mapper = $mapper;
        $this->padesApi = $config->getPadesApi();
        $this->eidEasyApi = $config->getApi();
    }

    public function fetchByToken(string $token): void
    {
        /** @var Session $session */
        $session = $this->mapper->findByToken($token);

        $this->fetch($session);
    }

    public function fetchByDocId(string $docId): Session
    {
        /** @var Session $session */
        $session = $this->mapper->findByDocId($docId);

        $this->fetch($session);

        return $session;
    }

    public function fetch(Session $session): void
    {
        if ((bool)$session->getIsDownloaded()) {
            return;
        }

        // Mark that the session is used, aka the file is downloaded. This
        // prevents race condition, where there are two simultaneous
        // callbacks which both fetch the signed file.
        $session->setIsDownloaded(1);
        $this->mapper->update($session);

        $isHashBased = (bool)$session->getIsHashBased();
        $containerType = $session->getContainerType();

        $data = $this->eidEasyApi->downloadSignedFile($session->getDocId());

        $signedFileContents = $data['signed_file_contents'];

        // Assemble signed file and make sure its in binary form.
        if (!$isHashBased) {
            $signedFileContents = base64_decode($signedFileContents);
        } elseif ($containerType === "pdf") {
            list($mimeType, $fileContent) = $this->getFile($session->getPath(), $session->getUserId());

            /** @var array $padesDssData */
            $padesDssData = $data['pades_dss_data'] ?? null;

            $padesResponse = $this->padesApi->addSignaturePades(
                $fileContent,
                $session->getSignatureTime(),
                $signedFileContents,
                $padesDssData
            );

            $signedFileContents = base64_decode($padesResponse['signedFile']);
        } elseif ($containerType === "asice") {
            $asice = new Asice();
            $unsignedContainer = $this->createAsiceContainer($session);
            $signedFileContents = $asice->addSignatureAsice($unsignedContainer, base64_decode($signedFileContents));
        }

        $containerPath = $this->getContainerPath($session);
        $this->saveContainer($session, $signedFileContents, $containerPath);

        $session->setSignedPath($containerPath);
        $this->mapper->update($session);
    }

    /**
     * Creates asice container and returns it in binary form.
     * */
    private function createAsiceContainer(Session $session): string
    {
        list($mimeType, $fileContent, $fileName) = $this->getFile($session->getPath(), $session->getUserId());

        $sourceFiles = [
            [
                'fileName' => $fileName,
                'fileContent' => $fileContent,
                'mimeType' => $mimeType,
            ]
        ];

        $asice = new Asice();
        return $asice->createAsiceContainer($sourceFiles);
    }

    private function saveContainer(Session $session, string $contents, string $containerPath): void
    {
        $userFolder = $this->storage->getUserFolder($session->getUserId());

        $userFolder->touch($containerPath);
        $userFolder->newFile($containerPath, $contents);
    }

    private function getContainerPath(Session $session): string
    {
        $originalPath = $session->getPath();
        $originalParts = explode('.', $originalPath);

        // Remove file extension.
        array_pop($originalParts);
        $fileName = implode('.', $originalParts);

        // Add date
        if (!str_contains($fileName, '_eidSignedAt-')) {
            $dateTime = (new \DateTime)->format('Ymd-His');
            $fileName = $fileName.'_eidSignedAt-'.$dateTime;
        }

        return $fileName.'.'.$session->getContainerType();
    }
}
