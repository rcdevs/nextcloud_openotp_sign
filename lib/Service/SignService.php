<?php
namespace OCA\OpenOTPSign\Service;

use OCA\OpenOTPSign\Commands\GetsFile;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Accounts\IAccountManager;

use OCP\Files\IRootFolder;
use OCP\IConfig;

use OCA\OpenOTPSign\Db\SignSession;
use OCA\OpenOTPSign\Db\SignSessionMapper;

class SignService {
    use GetsFile;

	const ASYNC_SIGN_TIME_OUT = 3600;

	private $mapper;
	private $storage;
	private $accountManager;
	private $userManager;

    // Settings
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

	public function __construct(
        IRootFolder $storage,
		IConfig $config,
		IUserManager $userManager,
		IAccountManager $accountManager,
		SignSessionMapper $mapper)
	{
		$this->mapper = $mapper;
		$this->storage = $storage;
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

    public function advancedSign($path, $userId, $remoteAddress) {
		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

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

		$data  = '<div style="color: black; background-color: white; border-radius: 10px; padding: 5px;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);
		$resp = $client->openotpNormalConfirm(
			$userId,
			$this->defaultDomain,
			$data,
			$fileContent,
			null,
			null,
			false,
			120,
			$account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
			$this->clientId,
			$remoteAddress,
			$this->userSettings,
			null
		);

		if ($resp['code'] === 1) {
			if (str_ends_with(strtolower($path), ".pdf")) {
				if ($this->signedFile == "overwrite") {
					$newPath = $path;
				} else {
					$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
				}
			} else {
				$newPath = $path . ".p7s";
			}

			$this->saveContainer($userId, $resp['file'], $newPath);
		}

        return $resp;
    }

    public function asyncAdvancedSign($path, $username, $userId, $remoteAddress, $email) {
		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

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

		$data  = '<div style="color: black; background-color: white; border-radius: 10px; padding: 5px;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

		if (str_contains($username, '@')) {
			$resp = $client->openotpExternConfirm(
				$username,
				$fileContent,
				false,
				true,
				self::ASYNC_SIGN_TIME_OUT,
				$sender,
				$this->clientId,
				$remoteAddress,
				$this->userSettings
			);
		} else {
			$resp = $client->openotpNormalConfirm(
				$username,
				$this->defaultDomain,
				$data,
				$fileContent,
				null,
				null,
				true,
				self::ASYNC_SIGN_TIME_OUT,
				$sender,
				$this->clientId,
				$remoteAddress,
				$this->userSettings,
				null
			);
		}

		if ($resp['code'] === 2) {
			$signSession = new SignSession();
			$signSession->setUid($userId);
			$signSession->setPath($path);
			$signSession->setRecipient($username);
			$signSession->setSession($resp['session']);
			$this->mapper->insert($signSession);

			// Generate and send QR Code
			if (!empty($email)) {
				$resp2 = $client->openotpTouchConfirm($resp['session'], false, "PNG", 5, 3);
				$this->sendQRCodeByEmail('advanced', $sender, $email, $resp2['qrImage']);
			}
		}

        return $resp;
    }

    public function qualifiedSign($path, $userId, $remoteAddress) {
		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

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

		$data  = '<div style="color: black; background-color: white; border-radius: 10px; padding: 5px;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);
		$resp = $client->openotpNormalSign(
			$userId,
			$this->defaultDomain,
			$data,
			$fileContent,
			'',
			false,
			120,
			$account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
			$this->clientId,
			$remoteAddress,
			$this->userSettings,
			null
		);

		if ($resp['code'] === 1) {
			if (str_ends_with(strtolower($path), ".pdf")) {
				if ($this->signedFile == "overwrite") {
					$newPath = $path;
				} else {
					$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
				}
			} else {
				$newPath = $path . ".p7s";
			}

			$this->saveContainer($userId, $resp['file'], $newPath);
		}

        return $resp;
    }

    public function asyncQualifiedSign($path, $username, $userId, $remoteAddress, $email) {
		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

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

		$data  = '<div style="color: black; background-color: white; border-radius: 10px; padding: 5px;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

		if (str_contains($username, '@')) {
			$resp = $client->openotpExternSign(
				$username,
				$fileContent,
				'',
				true,
				self::ASYNC_SIGN_TIME_OUT,
				$sender,
				$this->clientId,
				$remoteAddress,
				$this->userSettings
			);
		} else {
			$resp = $client->openotpNormalSign(
				$username,
				$this->defaultDomain,
				$data,
				$fileContent,
				'',
				true,
				self::ASYNC_SIGN_TIME_OUT,
				$sender,
				$this->clientId,
				$remoteAddress,
				$this->userSettings,
				null
			);
		}

		if ($resp['code'] === 2) {
			$signSession = new SignSession();
			$signSession->setUid($userId);
			$signSession->setPath($path);
			$signSession->setIsQualified(true);
			$signSession->setRecipient($username);
			$signSession->setSession($resp['session']);
			$this->mapper->insert($signSession);

			// Generate and send QR Code
			if (!empty($email)) {
				$resp2 = $client->openotpTouchSign($resp['session'], false, "PNG", 5, 3);
				$this->sendQRCodeByEmail('qualified', $sender, $email, $resp2['qrImage']);
			}
		}

        return $resp;
    }

    public function seal($path, $userId, $remoteAddress) {
		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

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

		ini_set('default_socket_timeout', 600);
		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);
		$resp = $client->openotpSeal(
			$fileContent,
			'',
			$this->clientId,
			$remoteAddress,
			'CaDESMode=Detached,'.$this->userSettings
		);

		if ($resp['code'] === 1) {
			if (str_ends_with(strtolower($path), ".pdf")) {
				if ($this->signedFile == "overwrite") {
					$newPath = $path;
				} else {
					$newPath = substr_replace($path, "-sealed", strrpos($path, '.'), 0);
				}
			} else {
				$newPath = $path . ".p7s";
			}

			$this->saveContainer($userId, $resp['file'], $newPath);
		}

        return $resp;
    }

    public function checkAsyncSignature() {
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

        $client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

        $signSessions = $this->mapper->findAllPending();
        foreach ($signSessions as $signSession) {
			if (!$signSession->getIsQualified()) {
				$resp = $client->openotpCheckConfirm($signSession->getSession());
			} else {
				$resp = $client->openotpCheckSign($signSession->getSession());
			}

            if ($resp['code'] === 1) {
				$path = $signSession->getPath();
				if (str_ends_with(strtolower($path), ".pdf")) {
					if ($this->signedFile == "overwrite") {
						$newPath = $path;
					} else {
						$newPath = substr_replace($path, "-{$signSession->getRecipient()}-signed", strrpos($path, '.'), 0);
					}
				} else {
					$newPath = $path . ".p7s";
				}

				$this->saveContainer($signSession->getUid(), $resp['file'], $newPath);

                $signSession->setIsPending(false);
                $this->mapper->update($signSession);
            } else if ($resp['code'] === 0) {
                $signSession->setIsPending(false);
                $signSession->setIsError(true);
                $signSession->setMessage($resp['message']);
                $this->mapper->update($signSession);
            }
        }
    }

	public function openotpStatus(IRequest $request) {
		$opts = array('location' => $request->getParam('server_url'));

		if ($request->getParam('ignore_ssl_errors')) {
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

		if ($request->getParam('use_proxy')) {
			$opts['proxy_host'] = $request->getParam('proxy_host');
			$opts['proxy_port'] = $request->getParam('proxy_port');
			$opts['proxy_login'] = $request->getParam('proxy_username');
			$opts['proxy_password'] = $request->getParam('proxy_password');
		}

		$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);
		return $client->openotpStatus();
	}

	private function sendQRCodeByEmail($signType, $sender, $recipient, $qrCode) {
		$boundary = "-----=".md5(uniqid(rand()));

		$headers = "From: OpenOTP Sign Nextcloud <no-reply>\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
		$headers .= "\r\n";

		$msg = "This message is in MIME 1.0 multipart/mixed format.\r\n";

		$msg .= "--$boundary\r\n";
		$msg .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
		$msg .= "Content-Transfer-Encoding:8bit\r\n";
		$msg .= "\r\n";
		$msg .= "<html><body>A new QuickSign $signType signature request has been sent to your mobile phone.<br>";
		$msg .= "The sender is <b>$sender</b>.<br>";
		$msg .= "The signature request will expire in ".round(self::ASYNC_SIGN_TIME_OUT / 60)." minutes (".date("Y-m-d H:i", time() + self::ASYNC_SIGN_TIME_OUT).").<br><br>";
		$msg .= "If you did not receive the mobile push notification, you can scan the following QRCode:<br><br>";
		$msg .= "<img src=\"cid:image1\">";
		$msg .= "</body></html>\r\n\r\n";

		$msg .= "--$boundary\r\n";
		$msg .= "Content-Type: application/octet-stream; name=\"qrcode.png\"\r\n";
		$msg .= "Content-Transfer-Encoding: base64\r\n";
		$msg .= "Content-ID: <image1>\r\n";
		$msg .= "\r\n";
		$msg .= base64_encode($qrCode) . "\r\n";
		$msg .= "\r\n\r\n";

		$msg .= "--$boundary\r\n";

		mail($recipient, "Signature request invitation", $msg, $headers);
	}
}