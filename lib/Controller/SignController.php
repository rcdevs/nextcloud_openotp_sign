<?php
namespace OCA\OpenOTPSign\Controller;

use OCA\OpenOTPSign\Commands\GetsFile;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;

class SignController extends Controller {
	use GetsFile;

	private $storage;
	private $userId;
	private $accountManager;
	private $userManager;
	private $serverUrl;
	private $ignoreSslErrors;
	private $clientId;
	private $defaultDomain;
	private $userSettings;
	private $useProxy;
	private $proxyHost;
	private $proxyPort;
	private $proxyUsername;
	private $proxyPassword;
	private $signedFile;

	public function __construct($AppName, IRequest $request, IRootFolder $storage, IConfig $config, $UserId, IUserManager $userManager, IAccountManager $accountManager){
		parent::__construct($AppName, $request);
		$this->storage = $storage;
		$this->userId = $UserId;
		$this->accountManager = $accountManager;
		$this->userManager = $userManager;

		$this->serverUrl = $config->getAppValue('openotpsign', 'server_url');
		$this->ignoreSslErrors = $config->getAppValue('openotpsign', 'ignore_ssl_errors');
		$this->clientId = $config->getAppValue('openotpsign', 'client_id');
		$this->defaultDomain = $config->getAppValue('openotpsign', 'default_domain');
		$this->userSettings = $config->getAppValue('openotpsign', 'user_settings');
		$this->useProxy = $config->getAppValue('openotpsign', 'use_proxy');
		$this->proxyHost = $config->getAppValue('openotpsign', 'proxy_host');
		$this->proxyPort = $config->getAppValue('openotpsign', 'proxy_port');
		$this->proxyUsername = $config->getAppValue('openotpsign', 'proxy_username');
		$this->proxyPassword = $config->getAppValue('openotpsign', 'proxy_password');
		$this->signedFile = $config->getAppValue('openotpsign', 'signed_file');
	}

	/**
	 * @NoAdminRequired
	 */
	public function advancedSign() {
		$path = $this->request->getParam('path');
		list($mimeType, $fileContent, $fileName) = $this->getFile($path, $this->userId);

		$opts = array('location' => $this->serverUrl);
		if ($this->ignoreSslErrors) {
			$context = stream_context_create([
				'ssl' => [
					// set some SSL/TLS specific options
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				]
			]);

			$opts['stream_context'] = $context;
		}

		if ($this->useProxy) {
			$opts['proxy_host'] = $this->proxyHost;
			$opts['proxy_port'] = $this->proxyPort;
			$opts['proxy_login'] = $this->proxyUsername;
			$opts['proxy_password'] = $this->proxyPassword;
		}

		$user = $this->userManager->get($this->userId);
		$account = $this->accountManager->getAccount($user);

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);
		$resp = $client->openotpNormalConfirm(
			$this->userId,
			$this->defaultDomain,
			"NextCloud signature request for " . $fileName,
			$fileContent,
			null,
			null,
			false,
			120,
			$account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
			$this->clientId,
			$_SERVER['REMOTE_ADDR'],
			$this->userSettings,
			null
		);

		if ($resp['code'] === 1) {
			if ($this->signedFile == "overwrite") {
				$newPath = $path;
			} else {
				$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
			}

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

		$opts = array('location' => $this->serverUrl);
		if ($this->ignoreSslErrors) {
			$context = stream_context_create([
				'ssl' => [
					// set some SSL/TLS specific options
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				]
			]);

			$opts['stream_context'] = $context;
		}

		if ($this->useProxy) {
			$opts['proxy_host'] = $this->proxyHost;
			$opts['proxy_port'] = $this->proxyPort;
			$opts['proxy_login'] = $this->proxyUsername;
			$opts['proxy_password'] = $this->proxyPassword;
		}

		$user = $this->userManager->get($this->userId);
		$account = $this->accountManager->getAccount($user);

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);
		$resp = $client->openotpNormalSign(
			$this->userId,
			$this->defaultDomain,
			"NextCloud signature request for " . $fileName,
			$fileContent,
			'',
			false,
			120,
			$account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
			$this->clientId,
			$_SERVER['REMOTE_ADDR'],
			$this->userSettings,
			null
		);

		if ($resp['code'] === 1) {
			if ($this->signedFile == "overwrite") {
				$newPath = $path;
			} else {
				$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
			}

			$this->saveContainer($this->userId, $resp['file'], $newPath);
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}
}
