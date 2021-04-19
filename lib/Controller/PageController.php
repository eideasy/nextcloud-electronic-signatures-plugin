<?php
namespace OCA\ElectronicSignatures\Controller;

use OCA\ElectronicSignatures\Config;
use OCA\ElectronicSignatures\Db\Session;
use OCA\ElectronicSignatures\Db\SessionMapper;
use OCA\ElectronicSignatures\Exceptions\EidEasyException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
    private $userId;

    /** @var  IClientService */
    private $httpClientService;

    /** @var IRootFolder */
    private $storage;

    /** @var SessionMapper */
    private $mapper;

    /** @var Config */
    private $config;

	public function __construct(
	    $AppName,
        IRequest $request,
        IRootFolder $storage,
        IClientService $clientService,
        SessionMapper $mapper,
        Config $config,
        $UserId
    ){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
        $this->storage = $storage;
        $this->httpClientService = $clientService;
        $this->mapper = $mapper;
        $this->config = $config;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 * TODO remove this useless endpoint?
	 *
     * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		return new TemplateResponse('electronicsignatures', 'index');  // templates/index.php
	}

    /**
     * User will be redirected here from eID Easy, after they have successfully signed their document
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function signingCallback() {
        $token = $path = $this->request->getParam('token');

        $session = $this->mapper->findByToken($token);

        $responseBody = $this->getContainerResponse($session);

        if (!isset($responseBody['signed_file_contents'])) {
            $message = isset($responseBody['message']) ? $responseBody['message'] : 'eID Easy error!';
            throw new EidEasyException($message);
        }

        $this->saveContainer($responseBody['signed_file_contents'], $session);

        // TODO redirect to success page.
        return new JSONResponse(['message' => 'SIGNED SUCCESSFULLY!']);
    }

    private function getContainerResponse(Session $session): array {
        // Download signed doc.
        // Send file to eID Easy server.
        $body = [
            'doc_id' => $session->getDocId(),
            'client_id' => $this->config->getClientId(),
            'secret' => $this->config->getSecret(),
        ];

        $client = $this->httpClientService->newClient();
        $response = $client->post($this->config->getUrl('api/signatures/download-signed-asice'), [
            'body' => json_encode($body),
            'headers' => [
                // TODO dynamically get the plugin version and inject to User-Agent.
                'User-Agent' => 'NextCloud-plugin',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function saveContainer(string $base64Content, Session $session): void {
        $userFolder = $this->storage->getUserFolder($session->getUserId());

        $path = $this->getContainerPath($session);
        $userFolder->touch($path);
        $userFolder->newFile($path, base64_decode($base64Content));
    }

    private function getContainerPath(Session $session): string {
        $originalPath = $session->getPath();
        $parts = explode('.', $originalPath);

        // Remove file extension.
        array_pop($parts);

        $beginning = implode('.', $parts);
        $extension = SignApiController::CONTAINER_TYPE;
        return "$beginning-{$session->getToken()}.$extension";
    }
}
