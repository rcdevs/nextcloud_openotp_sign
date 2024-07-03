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

use Exception;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstCommon;
use OCA\OpenOTPSign\Utils\CstOOtpSign;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use SoapClient;
use SoapHeader;

class SoapService
{
	const openOtpStatus			= 'openotpStatus';
	const openOtpNormalConfirm	= 'openotpNormalConfirm';

	private SoapClient $soapClient;
	private array $options;

	public function __construct(
		private ConfigurationService $configurationService,
		private LogRCDevs $logRCDevs,
		string $serverUrl,
	) {
		try {
			$context = stream_context_create([
				'ssl' => [
					// set some SSL/TLS specific options
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				]
			]);
			
			$this->soapClient = new SoapClient(null, array(
				'location' 			=> $serverUrl,
				'uri'	  			=> $serverUrl,
				'proxy_host'	 	=> $this->configurationService->proxy()->host,
				'proxy_port'	 	=> $this->configurationService->proxy()->port,
				'proxy_login'		=> $this->configurationService->proxy()->username,
				'proxy_password' 	=> $this->configurationService->proxy()->password,
				'encoding'			=> 'UTF-8',
				'stream_context' => $context
			));

			// Build headers
			$headers = array();

			$headers[] = new SoapHeader(
				'http://soapinterop.org/echoheader/',
				'Content-type',
				'text/xml;charset="utf-8"'
			);

			$headers[] = new SoapHeader(
				'http://soapinterop.org/echoheader/',
				'WA-API-Key',
				$this->configurationService->apiKey()
			);

			$this->soapClient->__setSoapHeaders($headers);

			// Add options
			$this->options = $this->addSoapOptions();
		} catch (\Throwable $th) {
			$this->logRCDevs->error(vsprintf('Soap Service building failed for server "%s" [%s]', [$serverUrl, $th->getMessage()]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
			throw $th;
		}
	}

	public function openOtpStatus(): array
	{
		try {
			return $this->soapClient->__soapCall(self::openOtpStatus, array(), $this->options);
		} catch (\Throwable $th) {
			$this->logRCDevs->error(vsprintf('Soap Service call failed for "%s" [%s]', [self::openOtpStatus, $th->getMessage()]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
			throw $th;
		}
	}

	public function openOtpNormalConfirm(array $intel): array
	{
		$returned = [];

		try {
			$resp = $this->soapClient->__soapCall(self::openOtpNormalConfirm, $intel, $this->options);
			if ($resp[Constante::ootpsign(CstOOtpSign::CODE)] !== 1) {
				throw new Exception($resp[Constante::ootpsign(CstOOtpSign::MESSAGE)], 1);
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(vsprintf('Soap Service call failed for "%s" [%s]', [self::openOtpNormalConfirm, $th->getMessage()]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
			$returned = [
				Constante::ootpsign(CstOOtpSign::CODE)		=> 0,
				// Constante::ootpsign(CstOOtpSign::MESSAGE)	=> $resp['faultcode'] . ' / ' . $resp['faultstring'],
				Constante::ootpsign(CstOOtpSign::ERROR)		=> $th->getCode(),
				Constante::ootpsign(CstOOtpSign::MESSAGE)	=> $th->getMessage(),
			];
			throw $th;
		}

		return $returned;
	}

	private function addSoapOptions()
	{
		$context = stream_context_create([
			'ssl' => [
				// set some SSL/TLS specific options
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		]);

		return [
			'stream_context'	=> $context,
			'style'				=> SOAP_RPC,
			'uri'				=> 'urn:openotp',
			'use'				=> SOAP_LITERAL,
		];
	}
}
