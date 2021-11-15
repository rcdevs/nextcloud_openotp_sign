<?php
namespace OCA\OpenOTPSign\Cron;

use \OCP\BackgroundJob\TimedJob;
use \OCP\AppFramework\Utility\ITimeFactory;

use OCA\OpenOTPSign\Service\SignService;

class CheckAsyncSignatureTask extends TimedJob {

	private $signService;

    public function __construct(ITimeFactory $time, SignService $signService) {
        parent::__construct($time);
		$this->signService = $signService;

        // Run every 5 minutes
        parent::setInterval(300);
    }

    protected function run($arguments) {
		$this->signService->checkAsyncSignature();
    }
}