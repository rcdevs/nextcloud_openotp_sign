<?php
namespace OCA\OpenOTPSign\Service;

use OCA\OpenOTPSign\Commands\GetsFile;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Accounts\IAccountManager;

use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;

use OCA\OpenOTPSign\Db\SignSession;
use OCA\OpenOTPSign\Db\SignSessionMapper;
use OCP\AppFramework\Db\DoesNotExistException;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class SignService {
    use GetsFile;

	const CNX_TIME_OUT = 1;

	private $mapper;
	private $storage;
	private $accountManager;
	private $userManager;

    // Settings
	private $serverUrls;
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
	private $syncTimeout;
	private $asyncTimeout;
	private $enableDemoMode;
	private $watermarkText;

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

		$this->serverUrls = json_decode($config->getAppValue('openotp_sign', 'server_urls', '[]'));
		$this->ignoreSslErrors = $config->getAppValue('openotp_sign', 'ignore_ssl_errors');
		$this->clientId = $config->getAppValue('openotp_sign', 'client_id');
		$this->defaultDomain = $config->getAppValue('openotp_sign', 'default_domain');
		$this->userSettings = $config->getAppValue('openotp_sign', 'user_settings');
		$this->useProxy = $config->getAppValue('openotp_sign', 'use_proxy');
		$this->proxyHost = $config->getAppValue('openotp_sign', 'proxy_host');
		$this->proxyPort = $config->getAppValue('openotp_sign', 'proxy_port');
		$this->proxyUsername = $config->getAppValue('openotp_sign', 'proxy_username');
		$this->proxyPassword = $config->getAppValue('openotp_sign', 'proxy_password');
		$this->signedFile = $config->getAppValue('openotp_sign', 'signed_file');
		$this->syncTimeout = (int) $config->getAppValue('openotp_sign', 'sync_timeout') * 60;
		$this->asyncTimeout = (int) $config->getAppValue('openotp_sign', 'async_timeout') * 3600;
		$this->enableDemoMode = $config->getAppValue('openotp_sign', 'enable_demo_mode');
		$this->watermarkText = $config->getAppValue('openotp_sign', 'watermark_text');
    }

	private function addWatermark(&$fileContent, $fileName, $isPdf) {

		if (!$this->enableDemoMode || !$isPdf) {
			return $fileContent;
		}

		// Source file and watermark config
		$imgPath = __DIR__.'/../../../../data/';
		$font = __DIR__.'/DejaVuSans-Bold.ttf';
		$opacity = 100;

		// Set source PDF file
		$pdf = new Fpdi();
		$pagecount = $pdf->setSourceFile(StreamReader::createByString($fileContent));

		// Add watermark to PDF pages
		for ($i = 1; $i <= $pagecount; $i++) {
			$tpl = $pdf->importPage($i);
			$size = $pdf->getTemplateSize($tpl);
			$pdf->addPage();
			$pdf->useTemplate($tpl, 1, 1, $size['width'], $size['height'], TRUE);

			$name = uniqid();

			// Convert dimensions of the page from millimeters to pixels
			$width  = $size['width'] * 3.7795275591;
			$height = $size['height'] * 3.7795275591;

			// Find angle of the diagonal and convert it from radians to degrees
			$angle = atan($height / $width) * (180.0 / M_PI);

			// Find max font size to fit the page
			$font_size = 1;
			$box = null;

			while (true) {
				$box = imagettfbbox($font_size, $angle, $font, $this->watermarkText);
				$text_width = abs($box[6]) + $box[2];
				if ($text_width > $width) {
					$font_size--;
					break;
				}

				$text_height = abs($box[5]) - $box[1];
				if ($text_height > $height) {
					$font_size--;
					break;
				}

				$font_size++;
			}

			$img = imagecreatetruecolor($width, $height);

			// Background color
			$bg = imagecolorallocate($img, 255, 255, 255);
			imagefilledrectangle($img, 0, 0, $width, $height, $bg);

			// Font color settings
			$color = imagecolorallocate($img, 255, 0, 0);

			imagettftext($img, $font_size, $angle, abs($box[6]), $height, $color, $font, $this->watermarkText);
			imagecolortransparent($img, $bg);
			$blank = imagecreatetruecolor($width, $height);
			$tbg = imagecolorallocate($blank, 255, 255, 255);
			imagefilledrectangle($blank, 0, 0, $width, $height, $tbg);
			imagecolortransparent($blank, $tbg);

			// Create watermark image
			imagecopymerge($blank, $img, 0, 0, 0, 0, $width, $height, $opacity);
			imagepng($blank, $imgPath.$name.".png");

			//Put the watermark
			$pdf->Image($imgPath.$name.'.png', 0, 0, 0, 0, 'png');
			@unlink($imgPath.$name.'.png');
		}

		// Return PDF with watermark
		return $pdf->Output('S');
	}

    public function advancedSign($path, $userId, $remoteAddress) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		ini_set('default_socket_timeout', 600);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpNormalConfirm(
					$userId,
					$this->defaultDomain,
					$data,
					$this->addWatermark($fileContent, $fileName, $isPdf),
					null,
					null,
					false,
					$this->syncTimeout,
					$account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
					$this->clientId,
					$remoteAddress,
					$this->userSettings,
					null
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

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

    public function asyncLocalAdvancedSign($path, $username, $userId, $remoteAddress, $email) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);
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

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		ini_set('default_socket_timeout', 600);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpNormalConfirm(
					$username,
					$this->defaultDomain,
					$data,
					$this->addWatermark($fileContent, $fileName, $isPdf),
					null,
					null,
					true,
					$this->asyncTimeout,
					$sender,
					$this->clientId,
					$remoteAddress,
					$this->userSettings,
					null
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

		if ($resp['code'] === 2) {
			$signSession = new SignSession();
			$signSession->setUid($userId);
			$signSession->setPath($path);
			$signSession->setRecipient($username);
			$signSession->setSession($resp['session']);

			$expirationDate = new \DateTime();
			$signSession->setExpirationDate($expirationDate->add(new \DateInterval('PT'.$this->asyncTimeout.'S')));

			$this->mapper->insert($signSession);

			// Generate and send QR Code
			if (!empty($email)) {
				$resp2 = $client->openotpTouchConfirm($resp['session'], false, "PNG", 5, 3);
				$this->sendQRCodeByEmail('advanced', $sender, $email, $resp2['qrImage'], $resp2['message']);
			}
		}

        return $resp;
    }

	public function asyncExternalAdvancedSign($path, $email, $userId, $remoteAddress) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);
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

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		ini_set('default_socket_timeout', 600);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpExternConfirm(
					$email,
					$this->addWatermark($fileContent, $fileName, $isPdf),
					false,
					true,
					$this->asyncTimeout,
					$sender,
					$this->clientId,
					$remoteAddress,
					$this->userSettings
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

		if ($resp['code'] === 2) {
			$signSession = new SignSession();
			$signSession->setUid($userId);
			$signSession->setPath($path);
			$signSession->setRecipient($email);
			$signSession->setSession($resp['session']);
			$signSession->setIsYumisign(true);

			$expirationDate = new \DateTime();
			$signSession->setExpirationDate($expirationDate->add(new \DateInterval('PT'.$this->asyncTimeout.'S')));

			$this->mapper->insert($signSession);
		}

        return $resp;
	}

    public function qualifiedSign($path, $userId, $remoteAddress) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		ini_set('default_socket_timeout', 600);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpNormalSign(
					$userId,
					$this->defaultDomain,
					$data,
					$this->addWatermark($fileContent, $fileName, $isPdf),
					'',
					false,
					$this->syncTimeout,
					$account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
					$this->clientId,
					$remoteAddress,
					$this->userSettings,
					null
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

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

    public function asyncLocalQualifiedSign($path, $username, $userId, $remoteAddress, $email) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		ini_set('default_socket_timeout', 600);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpNormalSign(
					$username,
					$this->defaultDomain,
					$data,
					$this->addWatermark($fileContent, $fileName, $isPdf),
					'',
					true,
					$this->asyncTimeout,
					$sender,
					$this->clientId,
					$remoteAddress,
					$this->userSettings,
					null
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

		if ($resp['code'] === 2) {
			$signSession = new SignSession();
			$signSession->setUid($userId);
			$signSession->setPath($path);
			$signSession->setIsQualified(true);
			$signSession->setRecipient($username);
			$signSession->setSession($resp['session']);

			$expirationDate = new \DateTime();
			$signSession->setExpirationDate($expirationDate->add(new \DateInterval('PT'.$this->asyncTimeout.'S')));

			$this->mapper->insert($signSession);

			// Generate and send QR Code
			if (!empty($email)) {
				$resp2 = $client->openotpTouchSign($resp['session'], false, "PNG", 5, 3);
				$this->sendQRCodeByEmail('qualified', $sender, $email, $resp2['qrImage'], $resp2['message']);
			}
		}

        return $resp;
    }

    public function asyncExternalQualifiedSign($path, $email, $userId, $remoteAddress) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		ini_set('default_socket_timeout', 600);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpExternSign(
					$email,
					$this->addWatermark($fileContent, $fileName, $isPdf),
					'',
					true,
					$this->asyncTimeout,
					$sender,
					$this->clientId,
					$remoteAddress,
					$this->userSettings
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

		if ($resp['code'] === 2) {
			$signSession = new SignSession();
			$signSession->setUid($userId);
			$signSession->setPath($path);
			$signSession->setIsQualified(true);
			$signSession->setRecipient($email);
			$signSession->setSession($resp['session']);
			$signSession->setIsYumisign(true);

			$expirationDate = new \DateTime();
			$signSession->setExpirationDate($expirationDate->add(new \DateInterval('PT'.$this->asyncTimeout.'S')));

			$this->mapper->insert($signSession);
		}

        return $resp;
    }

    public function seal($path, $userId, $remoteAddress) {

		$isPdf = str_ends_with(strtolower($path), ".pdf");

		if ($this->enableDemoMode && !$isPdf) {
			$resp['code'] = 0;
			$resp['message'] = $this->l->t("Demo mode enabled. It is only possible to sign PDF files.");
			return $resp;
		}

		list($fileContent, $fileName, $fileSize, $lastModified) = $this->getFile($path, $userId);

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				$resp = $client->openotpSeal(
					$this->addWatermark($fileContent, $fileName, $isPdf),
					'',
					$this->clientId,
					$remoteAddress,
					'CaDESMode=Detached,'.$this->userSettings
				);

				if ($resp === null) {
					$resp['code'] = 0;
					$resp['message'] = "File too big. Set the PHP directive post_max_size to 20M on the OpenOTP server.";
					return $resp;
				}

				break;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

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

	public function cancelSignRequest($session, $userId) {
		try {
			$signSession = $this->mapper->findBySession($session);
		} catch(DoesNotExistException $e) {
			$resp['code'] = 0;
			$resp['message'] = "Session not started or timedout";
			return $resp;
		}

		if ($signSession->getUid() !== $userId) {
			$resp['code'] = 403;
			$resp['message'] = "Forbidden";
			return $resp;
		}

		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
				if (!$signSession->getIsQualified()) {
					$resp = $client->openotpCancelConfirm($session);
				} else {
					$resp = $client->openotpCancelSign($session);
				}

				if ($resp['code'] === 1) {
					$signSession->setIsPending(false);
					$signSession->setIsError(true);
					$signSession->setMessage($resp['message']);
					$this->mapper->update($signSession);
				}

				return $resp;
			} catch (\Throwable $e) {
				$resp['code'] = 0;
				$resp['message'] = $e->getMessage();
			}
		}

		return $resp;
	}

    public function checkAsyncSignature() {
		$opts = array('connection_timeout' => self::CNX_TIME_OUT);

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

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$opts['location'] = $this->serverUrls[$i];
			$client = new \SoapClient(__DIR__.'/openotp.wsdl', $opts);

			try {
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
			} catch (\Throwable $e) {
			}
		}
    }

	public function openotpStatus(IRequest $request) {
		$opts = array('location' => $request->getParam('server_url'));
		$opts['connection_timeout'] = self::CNX_TIME_OUT;

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

	private function sendQRCodeByEmail($signType, $sender, $recipient, $qrCode, $uri) {
		$boundary = "-----=".md5(uniqid(rand()));
		$boundary2 = "-----=".md5(uniqid(rand()));

		$headers  = "From: OpenOTP Sign Nextcloud <no-reply>\r\n";
		$headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
		$headers .= "Mime-Version: 1.0\r\n";

		$msg .= "--$boundary\r\n";
		$msg .= "Content-Transfer-Encoding: 8bit\r\n";
		$msg .= "Content-Type: text/plain; charset=utf-8\r\n";
		$msg .= "\r\n";

		$msg .= "A new QuickSign $signType signature request has been sent to your mobile phone.\r\n";
		$msg .= "The sender is $sender.\r\n";
		$msg .= "The signature request will expire in ".round($this->asyncTimeout / 3600)." hour(s) (".date("Y-m-d H:i", time() + $this->asyncTimeout).").\r\n\r\n";
		$msg .= "If you did not receive the mobile push notification, you can scan the attached QRCode.\r\n";
		$msg .= "\r\n";

		$msg .= "--$boundary\r\n";
		$msg .= "Content-Type: multipart/related; type=\"text/html\"; boundary=\"$boundary2\"\r\n";
		$msg .= "\r\n";

		$msg .= "--$boundary2\r\n";
		$msg .= "Content-Transfer-Encoding: 8bit\r\n";
		$msg .= "Content-Type: text/html; charset=utf-8\r\n";
		$msg .= "\r\n";

		$msg .= "<html><body>A new QuickSign $signType signature request has been sent to your mobile phone.<br>";
		$msg .= "The sender is <b>$sender</b>.<br>";
		$msg .= "The signature request will expire in ".round($this->asyncTimeout / 3600)." hour(s) (".date("Y-m-d H:i", time() + $this->asyncTimeout).").<br><br>";
		$msg .= "If you did not receive the mobile push notification, you can scan the following QRCode (or directly tap on it from your phone where the <em>OpenOTP Token</em> app is installed):<br><br>";
		$msg .= "<a href=\"$uri\"><img src=\"cid:image1\"></a>";
		$msg .= "</body></html>\r\n";
		$msg .= "\r\n";

		$msg .= "--$boundary2\r\n";
		$msg .= "Content-Transfer-Encoding: base64\r\n";
		$msg .= "Content-Disposition: inline; filename=qrcode.png\r\n";
		$msg .= "Content-Type: image/png; name=\"qrcode.png\"\r\n";
		$msg .= "Content-ID: <image1>\r\n";
		$msg .= "\r\n";
		$msg .= base64_encode($qrCode) . "\r\n";

		$msg .= "--$boundary2--\r\n";
		$msg .= "\r\n";

		$msg .= "--$boundary--\r\n";

		mail($recipient, "Signature request invitation", $msg, $headers);
	}
}