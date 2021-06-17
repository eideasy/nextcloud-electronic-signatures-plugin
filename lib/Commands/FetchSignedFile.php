<?php

namespace OCA\ElectronicSignatures\Commands;

use EidEasy\Api\EidEasyApi;
use EidEasy\Signatures\Asice;
use EidEasy\Signatures\Pades;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;

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

    public function fetch(string $docId): void
    {
        /** @var Session $session */
        $session = $this->mapper->findByDocId($docId);

        $isHashBased = (bool)$session->getIsHashBased();
        $containerType = $session->getContainerType();

        $data = $this->eidEasyApi->downloadSignedFile($docId);

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

        $this->saveContainer($session, $signedFileContents);
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

    private function saveContainer(Session $session, string $contents): void
    {
        $userFolder = $this->storage->getUserFolder($session->getUserId());

        $path = $this->getContainerPath($session);
        $userFolder->touch($path);
        $userFolder->newFile($path, $contents);
    }

    private function getContainerPath(Session $session): string
    {
        $originalPath = $session->getPath();
        $originalParts = explode('.', $originalPath);

        // Remove file extension.
        array_pop($originalParts);

        $beginning = implode('.', $originalParts);
        return "$beginning-{$session->getToken()}.{$session->getContainerType()}";
    }
}
