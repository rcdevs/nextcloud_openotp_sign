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

namespace OCA\OpenOTPSign\Service;

use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Service\ProxyService;
use OCP\IConfig;

class ConfigurationService
{
	public function __construct(
		private IConfig $config,
	) {
	}

	/** ******************************************************************************************
	 * PRIVATE
	 ****************************************************************************************** */
	private function intCompare(string $columnName)
	{
		return (intval($this->config->getAppValue(RCDevsApp::APP_ID, $columnName)) === 1);
	}

	/** ******************************************************************************************
	 * PUBLIC
	 ****************************************************************************************** */

	public function apiKey(): string
	{
		try {
			return $this->config->getAppValue(RCDevsApp::APP_ID, 'api_key');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function clientId(): string
	{
		try {
			return $this->config->getAppValue(RCDevsApp::APP_ID, 'client_id');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function doyouOverwrite(): bool
	{
		try {
			return $this->intCompare('overwrite');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function isEnabledDemoMode(): bool
	{
		try {
			return $this->intCompare('enable_demo_mode');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function isEnabledProxy(): bool
	{
		try {
			return $this->intCompare('use_proxy');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function isEnabledSeal(): bool
	{
		try {
			return $this->intCompare('enable_otp_seal');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function isEnabledSign(): bool
	{
		try {
			return $this->intCompare('enable_otp_sign');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function isEnabledSignTypeAdvanced(): bool
	{
		try {
			return $this->intCompare('sign_type_advanced');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function isEnabledSignTypeStandard(): bool
	{
		try {
			return $this->intCompare('sign_type_standard');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function proxy(): ProxyService
	{
		try {
			/** @var ProxyService $proxy*/
			$proxy = new ProxyService();

			if ($this->isEnabledProxy()) {
				foreach ($proxy as $key => $value) {
					$proxy->$key = $this->config->getAppValue(RCDevsApp::APP_ID, "proxy_{$key}");
				}
			} else {
				foreach ($proxy as $key => $value) {
					$proxy->$key = false;
				}
			}

			return $proxy;
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function serversUrls(): array
	{
		$returned = [];

		try {
			return json_decode($this->config->getAppValue(RCDevsApp::APP_ID, 'servers_urls'));
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function textualComplementSeal(): string
	{
		try {
			return $this->config->getAppValue(RCDevsApp::APP_ID, 'textual_complement_seal');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function textualComplementSign(): string
	{
		try {
			return $this->config->getAppValue(RCDevsApp::APP_ID, 'textual_complement_sign');
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function timeout(bool $asynchronous): int
	{
		try {
			if ($asynchronous) {
				return $this->config->getAppValue(RCDevsApp::APP_ID, 'async_timeout') * 86400;
			} else {
				return $this->config->getAppValue(RCDevsApp::APP_ID, 'sync_timeout') * 60;
			}
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	// public function watermarkText(): string
	// {
	// 	try {
	// 		return $this->isEnabledDemoMode() ? $this->config->getAppValue(RCDevsApp::APP_ID, 'watermark_text') : '';
	// 	} catch (\Throwable $th) {
	// 		throw $th;
	// 	}
	// }
}
