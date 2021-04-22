<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\ElectronicSignatures\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		['name' => 'page#signingCallback', 'url' => '/callback', 'verb' => 'GET'],
		['name' => 'signApi#getSignLink', 'url' => '/get_sign_link', 'verb' => 'GET'],
		['name' => 'settingsApi#updateCredentials', 'url' => '/update_credentials', 'verb' => 'POST'],
	]
];
