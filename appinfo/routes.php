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
	   ['name' => 'sign#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'sign#advancedSign', 'url' => '/advanced_sign', 'verb' => 'POST'],
	   ['name' => 'sign#asyncLocalAdvancedSign', 'url' => '/async_local_advanced_sign', 'verb' => 'POST'],
	   ['name' => 'sign#asyncExternalAdvancedSign', 'url' => '/async_external_advanced_sign', 'verb' => 'POST'],
	   ['name' => 'sign#qualifiedSign', 'url' => '/qualified_sign', 'verb' => 'POST'],
	   ['name' => 'sign#asyncLocalQualifiedSign', 'url' => '/async_local_qualified_sign', 'verb' => 'POST'],
	   ['name' => 'sign#asyncExternalQualifiedSign', 'url' => '/async_external_qualified_sign', 'verb' => 'POST'],
	   ['name' => 'sign#seal', 'url' => '/seal', 'verb' => 'POST'],
	   ['name' => 'sign#getLocalUsers', 'url' => '/get_local_users', 'verb' => 'GET'],
	   ['name' => 'settings#saveSettings', 'url' => '/settings', 'verb' => 'POST'],
	   ['name' => 'settings#checkServerUrl', 'url' => '/check_server_url', 'verb' => 'POST'],
	   ['name' => 'settings#checkSettings', 'url' => '/check_settings', 'verb' => 'GET'],
	   ['name' => 'requests#getPendingRequests', 'url' => '/pending_requests', 'verb' => 'GET'],
	   ['name' => 'requests#getCompletedRequests', 'url' => '/completed_requests', 'verb' => 'GET'],
	   ['name' => 'requests#getFailedRequests', 'url' => '/failed_requests', 'verb' => 'GET'],
    ]
];
