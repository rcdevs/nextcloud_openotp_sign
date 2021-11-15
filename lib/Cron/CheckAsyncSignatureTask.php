<?php
namespace OCA\OpenOTPSign\Cron;

use OCA\OpenOTPSign\Commands\GetsFile;
use \OCP\BackgroundJob\TimedJob;
use \OCP\AppFramework\Utility\ITimeFactory;

use OCA\OpenOTPSign\Db\SignSession;
use OCA\OpenOTPSign\Db\SignSessionMapper;

use OCP\Files\IRootFolder;
use OCP\IConfig;

class CheckAsyncSignatureTask extends TimedJob {
	use GetsFile;

    private $mapper;
	private $serverUrl;
	private $ignoreSslErrors;
	private $useProxy;
	private $proxyHost;
	private $proxyPort;
	private $proxyUsername;
	private $proxyPassword;
	private $signedFile;

    public function __construct(ITimeFactory $time, IConfig $config, SignSessionMapper $mapper, IRootFolder $storage) {
        parent::__construct($time);
        $this->mapper = $mapper;
		$this->storage = $storage;
        $this->serverUrl = $config->getAppValue('openotpsign', 'server_url');
		$this->ignoreSslErrors = $config->getAppValue('openotpsign', 'ignore_ssl_errors');
		$this->useProxy = $config->getAppValue('openotpsign', 'use_proxy');
		$this->proxyHost = $config->getAppValue('openotpsign', 'proxy_host');
		$this->proxyPort = $config->getAppValue('openotpsign', 'proxy_port');
		$this->proxyUsername = $config->getAppValue('openotpsign', 'proxy_username');
		$this->proxyPassword = $config->getAppValue('openotpsign', 'proxy_password');
		$this->signedFile = $config->getAppValue('openotpsign', 'signed_file');

        // Run every 5 minutes
        parent::setInterval(300);
    }

    protected function run($arguments) {
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

        $client = new \SoapClient(__DIR__.'/../Controller/openotp.wsdl', $opts);

        $signSessions = $this->mapper->findAllPending();
        foreach ($signSessions as $signSession) {
			if (!$signSession->getIsQualified()) {
				$resp = $client->openotpCheckConfirm($signSession->getSession());
			} else {
				$resp = $client->openotpCheckConfirm($signSession->getSession());
			}

            if ($resp['code'] === 1) {
				$path = $signSession->getPath();
				if (str_ends_with(strtolower($path), ".pdf")) {
					if ($this->signedFile == "overwrite") {
						$newPath = $path;
					} else {
						$newPath = substr_replace($path, "-signed", strrpos($path, '.'), 0);
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

}