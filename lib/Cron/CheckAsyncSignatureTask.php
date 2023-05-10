<?php
namespace OCA\OpenOTPSign\Cron;

use \OCP\BackgroundJob\TimedJob;
use \OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;

use OCA\OpenOTPSign\Service\SignService;

class CheckAsyncSignatureTask extends TimedJob {

	private $signService;

    public function __construct(ITimeFactory $time, SignService $signService, IConfig $config) {
        parent::__construct($time);
		$this->signService = $signService;

        $cron_interval = (int) $config->getAppValue('openotp_sign', 'cron_interval', 5) * 59;

        parent::setInterval($cron_interval);
    }

    protected function run($arguments) {
		$this->signService->checkAsyncSignature();
    }
}