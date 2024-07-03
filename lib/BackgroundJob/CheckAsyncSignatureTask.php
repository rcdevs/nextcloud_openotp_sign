<?php

/**
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OpenOTPSign\BackgroundJob;

use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Service\SignService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

class CheckAsyncSignatureTask extends TimedJob
{

	private $signService;

	public function __construct(ITimeFactory $time, SignService $signService, IConfig $config)
	{
		parent::__construct($time);
		$this->signService = $signService;

		$cron_interval = (int) $config->getAppValue(RCDevsApp::APP_ID, 'cron_interval', 5) * 59;

		parent::setInterval($cron_interval);
	}

	protected function run($arguments)
	{
		try {
			$this->signService->checkAsyncSignature();

			return null;
		} catch (\Throwable $th) {
			throw $th;
		}
	}
}
