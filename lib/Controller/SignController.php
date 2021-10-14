<?php
namespace OCA\OpenOTPSign\Controller;

use OCA\OpenOTPSign\Commands\GetsFile;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;

class SignController extends Controller {
	use GetsFile;

	private $storage;
	private $userId;
	private $serverUrl;

	public function __construct($AppName, IRequest $request, IRootFolder $storage, IConfig $config, $UserId){
		parent::__construct($AppName, $request);
		$this->storage = $storage;
		$this->userId = $UserId;

		$this->serverUrl = $config->getAppValue('openotpsign', 'server_url');
	}

	/**
	 * @NoAdminRequired
	 */
	public function advancedSign() {
		$path = $this->request->getParam('path');
		list($mimeType, $fileContent, $fileName) = $this->getFile($path, $this->userId);

		$context = stream_context_create([
			'ssl' => [
				// set some SSL/TLS specific options
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		]);

		$opts = array(
			'stream_context' => $context);

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient($this->serverUrl, $opts);
		$resp = $client->openotpNormalConfirm(
			$this->userId,
			"Demos",
			"NextCloud signature request for " . $fileName,
			$fileContent,
			null,
			null,
			false,
			120,
			'',
			"nextcloud",
			$_SERVER['REMOTE_ADDR'],
			null,
			null
		);

		if ($resp['code'] === 1) {
			$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
			$this->saveContainer($this->userId, $resp['file'], $newPath);
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function qualifiedSign() {
		$path = $this->request->getParam('path');
		list($mimeType, $fileContent, $fileName) = $this->getFile($path, $this->userId);

		$context = stream_context_create([
			'ssl' => [
				// set some SSL/TLS specific options
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		]);

		$opts = array(
			'stream_context' => $context);

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient($this->serverUrl, $opts);
		$resp = $client->openotpNormalSign(
			$this->userId,
			"Demos",
			"NextCloud signature request for " . $fileName,
			$fileContent,
			'',
			false,
			120,
			'',
			"nextcloud",
			$_SERVER['REMOTE_ADDR'],
			null,
			null
		);

		if ($resp['code'] === 1) {
			$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
			$this->saveContainer($this->userId, $resp['file'], $newPath);
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}
}
