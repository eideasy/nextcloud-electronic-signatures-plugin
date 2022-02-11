<?php

namespace OCA\ElectronicSignatures\Commands;

use OCA\ElectronicSignatures\Activity\ActivityManager;
use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Service\SigningLinkService;
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

    /** @var ActivityManager */
    private $activityManager;

    /** @var SigningLinkService */
    private $signingLinkService;

    /** @var SessionMapper */
    private $mapper;

    /** @var EidEasyApi */
    private $eidEasyApi;

    /** @var Pades */
    private $padesApi;

    public function __construct(
        IRootFolder        $storage,
        ActivityManager    $activityManager,
        SessionMapper      $mapper,
        SigningLinkService $signingLinkService,
        Config             $config
    )
    {
        $this->storage = $storage;
        $this->activityManager = $activityManager;
        $this->signingLinkService = $signingLinkService;
        $this->mapper = $mapper;
        $this->padesApi = $config->getPadesApi();
        $this->eidEasyApi = $config->getApi();
    }

    public function fetchByToken(string $token): void
    {
        /** @var Session $session */
        $session = $this->mapper->findByToken($token);

        $this->fetchFileAndSendNextEmail($session);
    }

    public function fetchByDocId(string $docId): void
    {
        /** @var Session $session */
        $session = $this->mapper->findByDocId($docId);

        $this->fetchFileAndSendNextEmail($session);
    }

    public function fetchFileAndSendNextEmail(Session $session): void
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

        $docId = $session->getDocId();
        $data = $this->eidEasyApi->downloadSignedFile($docId);
        $auditTrailData = $this->eidEasyApi->downloadAuditTrail($docId);
        $auditTrailContents = base64_decode($auditTrailData['audit_trail_file']);

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

        $session->setIsDocumentSigned(true);
        $this->mapper->update($session);

        $emails = $session->getSignerEmails();
        $userId = $session->getUserId();
        $signedPath = $session->getSignedPath();

        $this->signingLinkService->createFile(
            $userId,
            $signedPath,
            $containerType,
            $signedFileContents
        );

        $this->signingLinkService->downloadAuditTrail(
            $userId,
            $auditTrailContents,
            $auditTrailData['filename'],
            $signedPath
        );

        $this->activityManager->createAndTriggerEvent($session, $data);

        // Send email next in queue
        $this->signingLinkService->sendSignLinkToEmail($userId, $signedPath, $signedPath, $containerType, $emails);
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
}
