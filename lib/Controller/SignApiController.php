<?php

namespace OCA\ElectronicSignatures\Controller;

use JsonSchema\Exception\ValidationException;
use OCA\ElectronicSignatures\Commands\FetchSignedFile;
use OCA\ElectronicSignatures\Service\RemoteSigningQueueService;
use OCA\ElectronicSignatures\Service\SigningQueueService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

use EidEasy\Signatures\Pades;

class SignApiController extends OCSController
{
    private $userId;

    /** @var FetchSignedFile */
    private $fetchFileCommand;

    /** @var Pades */
    private $pades;

    /** @var LoggerInterface */
    private $logger;

    /** @var SigningQueueService */
    private $signingQueueService;

    /** @var RemoteSigningQueueService */
    private $remoteSigningQueueService;

    public function __construct(
        $AppName,
        IRequest $request,
        FetchSignedFile $fetchSignedFile,
        Pades $pades,
        LoggerInterface $logger,
        SigningQueueService $signingQueueService,
        RemoteSigningQueueService $remoteSigningQueueService,
        $UserId
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->fetchFileCommand = $fetchSignedFile;
        $this->signingQueueService = $signingQueueService;
        $this->pades = $pades;
        $this->logger = $logger;
        $this->remoteSigningQueueService = $remoteSigningQueueService;
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function createSigningQueue(): JSONResponse
    {
        try {
            $userId = $this->userId;
            $path = $this->request->getParam('path');
            $emailsInput = $this->request->getParam('emails');

            $this->signingQueueService->createSigningQueue(
                $userId,
                $path,
                $emailsInput
            );

            return new JSONResponse(['message' => 'E-mail sent!']);
        } catch (ValidationException $e) {
            return new JSONResponse([
                'message' => 'Provided email address is not valid',
            ], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse([
                'message' => "Failed to send email: {$e->getMessage()}"
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function createRemoteSigningQueue(): JSONResponse
    {
        $userId = $this->userId;
        $path = $this->request->getParam('path');

        $response = $this->remoteSigningQueueService->createSigningQueue($userId, $path);

        return new JSONResponse($response);
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function fetchSigningQueueFile(): JSONResponse
    {
        $queueId = $this->request->getParam('id');
        $signers = $this->request->getParam('signers');
        $signersCount = count($signers);
        $docId = $signers[$signersCount]['doc_id'];

        $this->remoteSigningQueueService->fetchSignedFile($queueId, $docId);

        return new JSONResponse(['message' => 'Fetched successfully!']);
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function getSigningQueue(): JSONResponse
    {
        try {
            $path = $this->request->getParam('path');
            $signingQueue = $this->signingQueueService->getQueueByPath($this->userId, $path);

            return new JSONResponse($signingQueue,  Http::STATUS_OK);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to fetch signing queue: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function updateSigningQueue(): JSONResponse
    {
        try {
            $path = $this->request->getParam('path');
            $emailsInput = $this->request->getParam('emails');

            $signingQueue = $this->signingQueueService->updateSigningQueue(
              $this->userId,
              $path,
              $emailsInput
            );

            return new JSONResponse($signingQueue,  Http::STATUS_OK);
        } catch (ValidationException $e) {
            return new JSONResponse(['message' => 'Provided email address is not valid',], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to update signing queue: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * eID Easy server will call this in the background when user signs document.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function fetchSignedFile(): JSONResponse
    {
        try {
            $docId = $this->request->getParam('doc_id');
            $this->fetchFileCommand->fetchByDocId($docId);

            return new JSONResponse(['message' => 'Fetched successfully!']);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to get link: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
