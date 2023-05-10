<?php
/**
 *
 * @copyright Copyright (c) 2021, RCDevs (info@rcdevs.com)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OpenOTPSign\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;

use OCA\OpenOTPSign\Service\SignService;

class SettingsController extends Controller {

	private $config;
	private $signService;

	public function __construct($AppName, IRequest $request, IConfig $config, SignService $signService){
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->signService = $signService;
	}

	public function saveSettings() {
		$this->config->setAppValue('openotp_sign', 'server_urls', json_encode($this->request->getParam('server_urls')));
		$this->config->setAppValue('openotp_sign', 'client_id', $this->request->getParam('client_id'));
		$this->config->setAppValue('openotp_sign', 'api_key', $this->request->getParam('api_key'));
		$this->config->setAppValue('openotp_sign', 'use_proxy', $this->request->getParam('use_proxy'));
		$this->config->setAppValue('openotp_sign', 'proxy_host', $this->request->getParam('proxy_host'));
		$this->config->setAppValue('openotp_sign', 'proxy_port', $this->request->getParam('proxy_port'));
		$this->config->setAppValue('openotp_sign', 'proxy_username', $this->request->getParam('proxy_username'));
		$this->config->setAppValue('openotp_sign', 'proxy_password', $this->request->getParam('proxy_password'));
		$this->config->setAppValue('openotp_sign', 'sign_scope', $this->request->getParam('sign_scope'));
		$this->config->setAppValue('openotp_sign', 'signed_file', $this->request->getParam('signed_file'));
		$this->config->setAppValue('openotp_sign', 'sync_timeout', $this->request->getParam('sync_timeout'));
		$this->config->setAppValue('openotp_sign', 'async_timeout', $this->request->getParam('async_timeout'));
		$this->config->setAppValue('openotp_sign', 'cron_interval', $this->request->getParam('cron_interval'));
		$this->config->setAppValue('openotp_sign', 'enable_demo_mode', $this->request->getParam('enable_demo_mode'));
		$this->config->setAppValue('openotp_sign', 'watermark_text', $this->request->getParam('watermark_text'));

		return new JSONResponse([
			'code' => 1,
		]);
	}

	public function checkServerUrl() {
		$resp = $this->signService->openotpStatus($this->request);

		if (isset($resp['status'])) {
			return new JSONResponse([
				'status' => $resp['status'],
				'message' => $resp['message']
			]);
		}

		return new JSONResponse([
			'status' => 'false',
			'message' => ''
		]);
	}

	public function checkSettings() {
		$serverUrls = json_decode($this->config->getAppValue('openotp_sign', 'server_urls', '[]'));
		$empty = true;

		foreach ($serverUrls as &$serverUrl) {
			if (!empty($serverUrl)) {
				$empty = false;
				break;
			}
		}

		return new JSONResponse(!$empty);
	}
}
