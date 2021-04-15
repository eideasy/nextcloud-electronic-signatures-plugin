<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class SignApiController extends Controller {
	public const CONTAINER_TYPE = 'asice';

    private $userId;

	/** @var  IClientService */
	private $httpClientService;

	/** @var IRootFolder */
	private $storage;

    /** @var SessionMapper */
    private $mapper;

	public function __construct($AppName, IRequest $request, IRootFolder $storage, IClientService $clientService, SessionMapper $mapper, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->storage = $storage;
		$this->mapper = $mapper;
		$this->httpClientService = $clientService;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * TODO re-enable CSRF check?
	 */
	public function getSignLink() {
        $path = $this->request->getParam('path');

		// TODO mark base64 ext as dependency in composer.json.
		$base64 = base64_encode($this->getFileContents($path));

        // TODO get credentials from config.
        // TODO de-validate these credentials in prod server, since they will be visible in git log.
        $clientId = 'TZJ0jMX0ukI49YgUrHrlIJEfo0R6jBGE';

        $token = $this->generateRandomString(32);

        $responseBody = $this->startSigningSession($path, $base64, $token);

		if (!isset($responseBody['doc_id'])) {
			$message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
			throw new EidEasyException($message);
		}

		// Return eID Easy server link
		$docId = $responseBody['doc_id'];

        $this->saveSession($token, $docId, $path);

        // TODO get base URL from config.
        $link = "https://id.eideasy.com/sign_contract_external?client_id=$clientId&doc_id=$docId";

		return new JSONResponse(['sign_link' => $link]);
	}

	private function getFileContents(string $path): string {
        $userFolder = $this->storage->getUserFolder($this->userId);

	    try {
            $file = $userFolder->get($path);

            if ($file instanceof \OCP\Files\File) {
                return $file->getContent();
            } else {
                // TODO test that when Accepts header is application/json, a decent error structure is returned.
                throw new NotFoundException('Can not read from folder');
            }
        } catch (\OCP\Files\NotFoundException $e) {
            throw new NotFoundException('File does not exist');
        }
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function startSigningSession(string $path, string $fileContentBase64, string $token): array {
        // TODO get credentials from config.
        // TODO de-validate these credentials in prod server, since they will be visible in git log.
        $clientId = 'TZJ0jMX0ukI49YgUrHrlIJEfo0R6jBGE';
        $secret = 'DxeBB3Ep1k9fyAd2jH55BAW4FXYQRfwS';

        // Send file to eID Easy server.
        $body = [
            'files' => [
                [
                    'fileName' => basename($path),
                    'fileContent' => $fileContentBase64,
                    'mimeType' => 'text/plain',
                ],
            ],
            'container_type' => self::CONTAINER_TYPE,
            // TODO get dynamic route.
            'signature_redirect' => "http://localhost:8080/index.php/apps/electronicsignatures/callback?token=$token",
            'client_id' => $clientId,
            'secret' => $secret
        ];

        // TODO get base URL from config.
        $client = $this->httpClientService->newClient();
        $response = $client->post('https://id.eideasy.com/api/signatures/prepare-files-for-signing', [
            'body' => json_encode($body),
            'headers' => [
                // TODO dynamically get the plugin version and inject to User-Agent.
                'User-Agent' => 'NextCloud-plugin',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        // TODO mark json ext as dependency in composer.json.
        return json_decode($response->getBody(), true);
    }

    private function saveSession(string $token, string $docId, string $path): void {
        // TODO  |  We should actually be getting the file by ID, not by path. Otherwise,
        // TODO  |  if it is moved after signature link is generated, then the
        // TODO  |  container is created in the wrong path.
	    $session = new Session();
        $session->setToken($token);
        $session->setDocId($docId);
        $session->setUserId($this->userId);
        $session->setPath($path);
        $session->setUsed(0);
        $this->mapper->insert($session);
    }
}
