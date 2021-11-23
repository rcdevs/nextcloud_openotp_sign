<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\OpenOTPSign\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
	   ['name' => 'sign#advancedSign', 'url' => '/advanced_sign', 'verb' => 'POST'],
	   ['name' => 'sign#asyncAdvancedSign', 'url' => '/async_advanced_sign', 'verb' => 'POST'],
	   ['name' => 'sign#qualifiedSign', 'url' => '/qualified_sign', 'verb' => 'POST'],
	   ['name' => 'sign#asyncQualifiedSign', 'url' => '/async_qualified_sign', 'verb' => 'POST'],
	   ['name' => 'sign#seal', 'url' => '/seal', 'verb' => 'POST'],
	   ['name' => 'sign#getLocalUsers', 'url' => '/get_local_users', 'verb' => 'GET'],
	   ['name' => 'settings#saveSettings', 'url' => '/settings', 'verb' => 'POST'],
	   ['name' => 'settings#checkServerUrl', 'url' => '/check_server_url', 'verb' => 'POST'],
	   ['name' => 'settings#checkSettings', 'url' => '/check_settings', 'verb' => 'GET'],
    ]
];
