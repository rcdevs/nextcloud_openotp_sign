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
		$this->config->setAppValue('openotpsign', 'use_proxy', $this->request->getParam('use_proxy'));
		$this->config->setAppValue('openotpsign', 'proxy_host', $this->request->getParam('proxy_host'));
		$this->config->setAppValue('openotpsign', 'proxy_port', $this->request->getParam('proxy_port'));
		$this->config->setAppValue('openotpsign', 'proxy_username', $this->request->getParam('proxy_username'));
		$this->config->setAppValue('openotpsign', 'proxy_password', $this->request->getParam('proxy_password'));

		return new JSONResponse([
			'code' => 1,
		]);
	}
}
