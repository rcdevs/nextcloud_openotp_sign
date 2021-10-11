<?php
namespace OCA\OpenOTPSign\Controller;

use OCA\OpenOTPSign\Commands\GetsFile;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;

class SignController extends Controller {
	use GetsFile;

	private $storage;
	private $userId;

	public function __construct($AppName, IRequest $request, IRootFolder $storage, $UserId){
		parent::__construct($AppName, $request);
		$this->storage = $storage;
		$this->userId = $UserId;
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
		$client = new \SoapClient("https://webadm.rcdevs.com/websrvs/wsdl.php?websrv=openotp", $opts);
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

}
