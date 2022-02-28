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
use nusoap_client;

class SignService {
    use GetsFile;

	const CNX_TIME_OUT = 1;

    /** @var IL10N */
    private $l;

	private $mapper;
	private $storage;
	private $accountManager;
	private $userManager;

    // Settings
	private $serverUrls;
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
		SignSessionMapper $mapper,
		IL10N $l)
	{
		$this->mapper = $mapper;
		$this->storage = $storage;
		$this->accountManager = $accountManager;
		$this->userManager = $userManager;
		$this->l = $l;

		$this->serverUrls = json_decode($config->getAppValue('openotp_sign', 'server_urls', '[]'));
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT, $this->syncTimeout);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpNormalConfirm', array(
				'username' => $userId,
				'domain' => $this->defaultDomain,
				'data' => $data,
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'form' => null,
				'scan' => false,
				'async' => false,
				'timeout' => $this->syncTimeout,
				'issuer' => $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => $this->userSettings,
				'virtual' => null
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '1') {
			if (str_ends_with(strtolower($path), ".pdf")) {
				if ($this->signedFile == "overwrite") {
					$newPath = $path;
				} else {
					$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
				}
			} else {
				$newPath = $path . ".p7s";
			}

			$this->saveContainer($userId, base64_decode($resp['file']), $newPath);
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpNormalConfirm', array(
				'username' => $username,
				'domain' => $this->defaultDomain,
				'data' => $data,
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'form' => null,
				'scan' => false,
				'async' => true,
				'timeout' => $this->asyncTimeout,
				'issuer' => $sender,
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => $this->userSettings,
				'virtual' => null
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '2') {
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
				$resp2 = $client->call('openotpTouchConfirm', array(
					'session' => $resp['session'],
					'sendPush' => false,
					'qrFormat' => 'PNG',
					'qrSizing' => 5,
					'qrMargin' => 3
				), 'urn:openotp', '', false, null, 'rpc', 'literal');
				$this->sendQRCodeByEmail('advanced', $sender, $email, base64_decode($resp2['qrImage']), $resp2['message']);
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpExternConfirm', array(
				'recipient' => $email,
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'scan' => false,
				'async' => true,
				'timeout' => $this->asyncTimeout,
				'issuer' => $sender,
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => $this->userSettings
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '2') {
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT, $this->syncTimeout);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpNormalSign', array(
				'username' => $userId,
				'domain' => $this->defaultDomain,
				'data' => $data,
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'mode' => '',
				'async' => false,
				'timeout' => $this->syncTimeout,
				'issuer' => $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => $this->userSettings,
				'virtual' => null
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '1') {
			if (str_ends_with(strtolower($path), ".pdf")) {
				if ($this->signedFile == "overwrite") {
					$newPath = $path;
				} else {
					$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
				}
			} else {
				$newPath = $path . ".p7s";
			}

			$this->saveContainer($userId, base64_decode($resp['file']), $newPath);
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$data  = '<div style="color: white;">';
		$data .= "<strong>Name: </strong>$fileName";
		$data .= "<br><strong>Size: </strong>".$this->humanFileSize($fileSize);
		$data .= "<br><strong>Modified: </strong>".date('m/d/Y H:i:s', $lastModified);
		$data .= '</div>';

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpNormalSign', array(
				'username' => $username,
				'domain' => $this->defaultDomain,
				'data' => $data,
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'mode' => '',
				'async' => true,
				'timeout' => $this->asyncTimeout,
				'issuer' => $sender,
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => $this->userSettings,
				'virtual' => null
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '2') {
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
				$resp2 = $client->call('openotpTouchSign', array(
					'session' => $resp['session'],
					'sendPush' => false,
					'qrFormat' => 'PNG',
					'qrSizing' => 5,
					'qrMargin' => 3
				), 'urn:openotp', '', false, null, 'rpc', 'literal');
				$this->sendQRCodeByEmail('qualified', $sender, $email, base64_decode($resp2['qrImage']), $resp2['message']);
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$sender = $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue();

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpExternSign', array(
				'recipient' => $email,
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'mode' => '',
				'async' => true,
				'timeout' => $this->asyncTimeout,
				'issuer' => $sender,
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => $this->userSettings
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '2') {
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$resp = $client->call('openotpSeal', array(
				'file' => base64_encode($this->addWatermark($fileContent, $fileName, $isPdf)),
				'mode' => '',
				'client' => $this->clientId,
				'source' => $remoteAddress,
				'settings' => 'CaDESMode=Detached,'.$this->userSettings
			), 'urn:openotp', '', false, null, 'rpc', 'literal');

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			break;
		}

		if ($resp['code'] === '1') {
			if (str_ends_with(strtolower($path), ".pdf")) {
				if ($this->signedFile == "overwrite") {
					$newPath = $path;
				} else {
					$newPath = substr_replace($path, "-sealed", strrpos($path, '.'), 0);
				}
			} else {
				$newPath = $path . ".p7s";
			}

			$this->saveContainer($userId, base64_decode($resp['file']), $newPath);
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

		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT, $this->syncTimeout);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			if (!$signSession->getIsQualified()) {
				$resp = $client->call('openotpCancelConfirm', array(
					$session
				), 'urn:openotp', '', false, null, 'rpc', 'literal');
			} else {
				$resp = $client->call('openotpCancelSign', array(
					$session
				), 'urn:openotp', '', false, null, 'rpc', 'literal');
			}

			if ($client->fault) {
				$resp['code'] = 0;
				$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
				break;
			}

			$err = $client->getError();
			if ($err) {
				$resp['code'] = 0;
				$resp['message'] = $err;
				continue;
			}

			if ($resp['code'] === '1') {
				$signSession->setIsPending(false);
				$signSession->setIsError(true);
				$signSession->setMessage($resp['message']);
				$this->mapper->update($signSession);
			}

			return $resp;
		}

		return $resp;
	}

    public function checkAsyncSignature() {
		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$nbServers = count($this->serverUrls);
		for ($i = 0; $i < $nbServers; ++$i) {
			$client = new nusoap_client($this->serverUrls[$i], false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
			$client->setDebugLevel(0);
			$client->soap_defencoding = 'UTF-8';
			$client->decode_utf8 = FALSE;

			$signSessions = $this->mapper->findAllPending();
			foreach ($signSessions as $signSession) {
				if (!$signSession->getIsQualified()) {
					$resp = $client->call('openotpCheckConfirm', array(
						$signSession->getSession()
					), 'urn:openotp', '', false, null, 'rpc', 'literal');
				} else {
					$resp = $client->call('openotpCheckSign', array(
						$signSession->getSession()
					), 'urn:openotp', '', false, null, 'rpc', 'literal');
				}

				if ($client->fault) {
					$resp['code'] = 0;
					$resp['message'] = $resp['faultcode'].' / '.$resp['faultstring'];
					break 2;
				}

				$err = $client->getError();
				if ($err) {
					$resp['code'] = 0;
					$resp['message'] = $err;
					break;
				}

				if ($resp['code'] === '1') {
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

					$this->saveContainer($signSession->getUid(), base64_decode($resp['file']), $newPath);

					$signSession->setIsPending(false);
					$this->mapper->update($signSession);
				} else if ($resp['code'] === '0') {
					$signSession->setIsPending(false);
					$signSession->setIsError(true);
					$signSession->setMessage($resp['message']);
					$this->mapper->update($signSession);
				}
			}

			break;
		}
    }

	public function openotpStatus(IRequest $request) {
		if ($this->useProxy) {
			$proxyHost = $this->proxyHost;
			$proxyPort = $this->proxyPort;
			$proxyUsername = $this->proxyUsername;
			$proxyPassword = $this->proxyPassword;
		} else {
			$proxyHost = false;
			$proxyPort = false;
			$proxyUsername = false;
			$proxyPassword = false;
		}

		$client = new nusoap_client($request->getParam('server_url'), false, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, self::CNX_TIME_OUT);
		$client->setDebugLevel(0);
		$client->soap_defencoding = 'UTF-8';
		$client->decode_utf8 = FALSE;

		return $client->call('openotpStatus', array());
	}

	private function sendQRCodeByEmail($signType, $sender, $recipient, $qrCode, $uri) {
		$boundary = "-----=".md5(uniqid(rand()));
		$boundary2 = "-----=".md5(uniqid(rand()));

		$headers  = "From: OpenOTP Sign Nextcloud <no-reply>\r\n";
		$headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
		$headers .= "Mime-Version: 1.0\r\n";

		$msg  = "--$boundary\r\n";
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