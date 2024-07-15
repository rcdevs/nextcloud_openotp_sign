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

namespace OCA\OpenOTPSign\Controller;

use Exception;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Service\SignService;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstConfig;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller
{
	public function __construct($AppName, IRequest $request, private IConfig $config, private SignService $signService)
	{
		parent::__construct($AppName, $request);
	}

	/** ******************************************************************************************
	 * PUBLIC
	 ****************************************************************************************** */

	public function checkCronStatus()
	{
		return new JSONResponse($this->signService->lastRunJob());
	}

	/**
	 * @NoAdminRequired
	 */
	public function checkEnabledOtp()
	{
		$signTypes['enableOtpSeal'] = $this->config->getAppValue(RCDevsApp::APP_ID, 'enable_otp_seal');
		$signTypes['enableOtpSign'] = $this->config->getAppValue(RCDevsApp::APP_ID, 'enable_otp_sign');

		return new JSONResponse($signTypes);
	}

	/**
	 * @NoAdminRequired
	 */
	public function checkServerUrl(int $serverNumber): JSONResponse
	{
		// Check requested server
		return new JSONResponse($this->signService->checkSettings($serverNumber));
	}

	/**
	 * @NoAdminRequired
	 */
	public function checkSettings(): JSONResponse // OpenOTP Sign
	{
		$returned = [];
		$message = null;

		try {
			$data = [];

			$respServerNumber = $this->signService->getServersNumber();
			if ($respServerNumber[Constante::request(CstRequest::CODE)] !== 1) {
				throw new Exception($respServerNumber[Constante::request(CstRequest::ERROR)], 1);
			}

			// Check all found servers in config
			for (
				$cptServers = 0;
				$cptServers < $respServerNumber[Constante::request(CstRequest::DATA)][Constante::config(CstConfig::SERVERS_NUMBER)];
				$cptServers++
			) {
				$data[$cptServers] = $this->signService->checkSettings($cptServers);
			}

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $message,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return new JSONResponse($returned);
	}

	/**
	 * @NoAdminRequired
	 */
	public function checkSignTypes()
	{
		$signTypes['signTypeStandard'] = $this->config->getAppValue(RCDevsApp::APP_ID, 'sign_type_standard');
		$signTypes['signTypeAdvanced'] = $this->config->getAppValue(RCDevsApp::APP_ID, 'sign_type_advanced');

		return new JSONResponse($signTypes);
	}

	public function resetJob()
	{
		return new JSONResponse($this->signService->resetJob());
	}

	public function saveSettings()
	{
		$returned = [];
		$message = null;

		try {
			$data = [];

			// Save app configuration
			$this->config->setAppValue(RCDevsApp::APP_ID, 'api_key',					$this->request->getParam('api_key'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'async_timeout',				$this->request->getParam('async_timeout'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'client_id',					$this->request->getParam('client_id'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'cron_interval',				$this->request->getParam('cron_interval'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'enable_demo_mode',			$this->request->getParam('enable_demo_mode'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'enable_otp_seal',			$this->request->getParam('enable_otp_seal'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'enable_otp_sign',			$this->request->getParam('enable_otp_sign'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'overwrite',					$this->request->getParam('overwrite'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'proxy_host',					$this->request->getParam('proxy_host'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'proxy_password',				$this->request->getParam('proxy_password'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'proxy_port',					$this->request->getParam('proxy_port'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'proxy_username',				$this->request->getParam('proxy_username'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'servers_urls',				json_encode($this->request->getParam('servers_urls')));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'sign_type_advanced',			$this->request->getParam('sign_type_advanced'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'sign_type_standard',			$this->request->getParam('sign_type_standard'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'sync_timeout',				$this->request->getParam('sync_timeout'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'textual_complement_seal',	$this->request->getParam('textual_complement_seal'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'textual_complement_sign',	$this->request->getParam('textual_complement_sign'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'use_proxy',					$this->request->getParam('use_proxy'));
			$this->config->setAppValue(RCDevsApp::APP_ID, 'watermark_text',				$this->request->getParam('watermark_text'));

			$message = 'Saved';

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $message,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return new JSONResponse($returned);
	}
}
