<?php

namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class SignController extends Controller {
	private $userId;

	/** @var  IClientService */
	private $httpClientService;

	/** @var IRootFolder */
	private $storage;

	public function __construct($AppName, IRequest $request, IRootFolder $storage, IClientService $clientService, $UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->storage = $storage;
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

		// Get file contents in base64.
		$userFolder = $this->storage->getUserFolder($this->userId);

		// Get file contents from storage.
		try {
			$file = $userFolder->get($path);

			if ($file instanceof \OCP\Files\File) {
				$fileContent = $file->getContent();
			} else {
				// TODO test that when Accepts header is application/json, a decent error structure is returned.
				throw new NotFoundException('Can not read from folder');
			}
		} catch (\OCP\Files\NotFoundException $e) {
			throw new NotFoundException('File does not exist');
		}

		// TODO mark base64 ext as dependency in composer.json.
		$base64 = base64_encode($fileContent);

		// Send file to eID Easy server.
		// TODO get credentials from config.
		// TODO de-validate these credentials in prod server, since they will be visible in git log.
		$clientId = 'TZJ0jMX0ukI49YgUrHrlIJEfo0R6jBGE';
		$secret = 'DxeBB3Ep1k9fyAd2jH55BAW4FXYQRfwS';
		$body = [
			'files' => [
				[
					'fileName' => basename($path),
					'fileContent' => $base64,
					'mimeType' => 'text/plain',
				],
			],
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
		$responseBody = json_decode($response->getBody(), true);

		if (!isset($responseBody['doc_id'])) {
			$message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
			throw new EidEasyException($message);
		}

		// Return eID Easy server link
		$docId = $responseBody['doc_id'];
		// TODO get base URL from config.
		$link = "https://id.eideasy.com/sign_contract_external?client_id=$clientId&doc_id=$docId";

		return new JSONResponse(['sign_link' => $link]);
	}
}
