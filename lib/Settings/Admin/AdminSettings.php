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

namespace OCA\OpenOTPSign\Settings\Admin;

use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Config;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings
{

	/** @var IConfig */
	// protected $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(
		private IConfig $config,
		private IInitialState $initialState,
		private LogRCDevs $logRCDevs,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse
	{
		$initialSettings = [
			'apiKey'				=> $this->config->getAppValue(RCDevsApp::APP_ID, 'api_key'),
			'asyncTimeout'			=> $this->config->getAppValue(RCDevsApp::APP_ID, 'async_timeout', 1),
			'clientId'				=> $this->config->getAppValue(RCDevsApp::APP_ID, 'client_id'),
			'cronInterval'			=> $this->config->getAppValue(RCDevsApp::APP_ID, 'cron_interval', 5),
			'enableDemoMode'		=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'enable_demo_mode')),
			'enableOtpSeal'			=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'enable_otp_seal')),
			'enableOtpSign'			=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'enable_otp_sign')),
			'installedVersion'		=> $this->config->getAppValue(RCDevsApp::APP_ID, 'installed_version'),
			'overwrite'		=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'overwrite')),
			'proxyHost'				=> $this->config->getAppValue(RCDevsApp::APP_ID, 'proxy_host'),
			'proxyPassword'			=> $this->config->getAppValue(RCDevsApp::APP_ID, 'proxy_password'),
			'proxyPort'				=> $this->config->getAppValue(RCDevsApp::APP_ID, 'proxy_port'),
			'proxyUsername'			=> $this->config->getAppValue(RCDevsApp::APP_ID, 'proxy_username'),
			'serversUrls'			=> (is_null(json_decode($this->config->getAppValue(RCDevsApp::APP_ID, 'servers_urls'))) ? ['', ''] : json_decode($this->config->getAppValue(RCDevsApp::APP_ID, 'servers_urls'))),
			'signTypeAdvanced'		=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'sign_type_advanced')),
			'signTypeStandard'		=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'sign_type_standard')),
			'syncTimeout'			=> $this->config->getAppValue(RCDevsApp::APP_ID, 'sync_timeout', 2),
			'textualComplementSeal'	=> $this->config->getAppValue(RCDevsApp::APP_ID, 'textual_complement_seal'),
			'textualComplementSign'	=> $this->config->getAppValue(RCDevsApp::APP_ID, 'textual_complement_sign'),
			'useProxy'				=> $this->convertForVueJsSwitch($this->config->getAppValue(RCDevsApp::APP_ID, 'use_proxy')),
			'watermarkText'			=> $this->config->getAppValue(RCDevsApp::APP_ID, 'watermark_text', 'RCDEVS - SPECIMEN - OPENOTP SIGN'),
		];

		$this->initialState->provideInitialState('initialSettings', $initialSettings);
		$this->logRCDevs->debug(sprintf('Initial settingsfrom DB : [%s]', json_encode($initialSettings)));

		Util::addScript(Config::APP_ID, Config::APP_ID . '-admin-settings');

		return new TemplateResponse(Config::APP_ID, 'settings/admin-settings', [], '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection()
	{
		return RCDevsApp::APP_ID;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority()
	{
		return 55;
	}

	private function convertForVueJsSwitch($settingToConvert): bool
	{
		switch (true) {
			case empty($settingToConvert):
				return false;
				break;

			case intval($settingToConvert) === 0:
				return false;
				break;

			case intval($settingToConvert) === 1:
				return true;
				break;

			default:
				return false;
				break;
		}
	}
}
