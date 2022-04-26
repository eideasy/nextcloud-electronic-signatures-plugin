<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Service\RemoteSigningQueueService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

require_once __DIR__ . '/../../vendor/autoload.php';

class RemoteQueueApiController extends OCSController
{
    private $userId;

    /** @var RemoteSigningQueueService */
    private $remoteSigningQueueService;

    public function __construct(
        $AppName,
        IRequest $request,
        RemoteSigningQueueService $remoteSigningQueueService,
        $UserId
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->remoteSigningQueueService = $remoteSigningQueueService;
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function createRemoteSigningQueue(): JSONResponse
    {
        $userId = $this->userId;
        $path = $this->request->getParam('path');
        $emailsInput = $this->request->getParam('emails');

        $response = $this->remoteSigningQueueService->createSigningQueue(
            $userId,
            $path,
            $emailsInput
        );

        return new JSONResponse($response);
    }

    /**
     * @return JSONResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function fetchSigningQueueFile(): JSONResponse
    {
        $queueId = $this->request->getParam('queue_id');
        $signers = $this->request->getParam('signers');
        $signerIndex = count($signers) - 1;
        $docId = $signers[$signerIndex]['doc_id'];

        $this->remoteSigningQueueService->fetchSignedFile($queueId, $docId);

        return new JSONResponse(['message' => 'Fetched successfully!']);
    }
}
