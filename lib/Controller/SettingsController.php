<?php
namespace OCA\OpenOTPSign\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;

class SettingsController extends Controller {

	private $userId;
	private $config;

	public function __construct($AppName, IRequest $request, IConfig $config, $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->config = $config;
	}

	public function saveSettings() {
		$this->config->setAppValue('openotpsign', 'server_url', $this->request->getParam('server_url'));
		$this->config->setAppValue('openotpsign', 'ignore_ssl_errors', $this->request->getParam('ignore_ssl_errors'));
		$this->config->setAppValue('openotpsign', 'client_id', $this->request->getParam('client_id'));
		$this->config->setAppValue('openotpsign', 'default_domain', $this->request->getParam('default_domain'));
		$this->config->setAppValue('openotpsign', 'user_settings', $this->request->getParam('user_settings'));

		return new JSONResponse([
			'code' => 1,
		]);
	}
}
