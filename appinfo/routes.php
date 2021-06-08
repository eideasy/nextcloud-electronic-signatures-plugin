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
		['name' => 'signApi#sendSignLinkByEmail', 'url' => '/send_sign_link_by_email', 'verb' => 'POST'],
		['name' => 'signApi#fetchSignedFile', 'url' => '/fetch_signed_file', 'verb' => 'POST'],
		['name' => 'settingsApi#getSettings', 'url' => '/settings', 'verb' => 'GET'],
		// TODO remove (deprecated)
        ['name' => 'settingsApi#updateSettingsDepr', 'url' => '/update_settings', 'verb' => 'POST'],
		['name' => 'settingsApi#updateSettings', 'url' => '/settings', 'verb' => 'POST'],
		['name' => 'sign#showSigningPage', 'url' => '/sign', 'verb' => 'GET'],
	]
];
