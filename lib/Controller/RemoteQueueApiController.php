<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Service\RemoteSigningQueueService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class RemoteQueueApiController extends OCSController
{
    private $userId;

    /** @var RemoteSigningQueueService */
    private $remoteSigningQueueService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        $AppName,
        IRequest $request,
        RemoteSigningQueueService $remoteSigningQueueService,
        LoggerInterface $logger,
        $UserId
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->remoteSigningQueueService = $remoteSigningQueueService;
        $this->logger = $logger;
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
     * @NoCSRFRequired
     * @PublicPage
     */
    public function fetchSigningQueueFile(): JSONResponse
    {
        try {
            $queueId = $this->getRequiredStringParam(['queue_id', 'signing_queue_id', 'id'], 'queue_id');
            $docId = $this->getCallbackDocId();

            $this->remoteSigningQueueService->fetchSignedFile($queueId, $docId);

            return new JSONResponse(['message' => 'Fetched successfully!']);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid remote signing queue callback: ' . $e->getMessage());
            return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->alert($e->getMessage() . "\n" . $e->getTraceAsString());
            return new JSONResponse(['message' => "Failed to fetch signing queue file: {$e->getMessage()}"], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    private function getRequiredStringParam(array $names, string $displayName): string
    {
        $value = $this->getStringParam($names);
        if ($value === null) {
            throw new \InvalidArgumentException("Missing $displayName");
        }

        return $value;
    }

    private function getCallbackDocId(): string
    {
        $docId = $this->getStringParam(['doc_id']);
        if ($docId !== null) {
            return $docId;
        }

        $data = $this->normalizeArrayParam($this->request->getParam('data'), 'data');
        if ($data !== null && isset($data['doc_id']) && trim((string)$data['doc_id']) !== '') {
            return (string)$data['doc_id'];
        }

        $signers = $this->normalizeArrayParam($this->request->getParam('signers'), 'signers');
        if ($signers !== null && !empty($signers)) {
            $lastSigner = $signers[array_key_last($signers)];
            if (is_array($lastSigner) && isset($lastSigner['doc_id']) && trim((string)$lastSigner['doc_id']) !== '') {
                return (string)$lastSigner['doc_id'];
            }
        }

        throw new \InvalidArgumentException('Missing doc_id');
    }

    private function getStringParam(array $names): ?string
    {
        foreach ($names as $name) {
            $value = $this->request->getParam($name);
            if (is_scalar($value) && trim((string)$value) !== '') {
                return (string)$value;
            }
        }

        return null;
    }

    private function normalizeArrayParam($value, string $name): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decodedValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decodedValue;
            }
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException("Invalid $name");
        }

        return $value;
    }
}
