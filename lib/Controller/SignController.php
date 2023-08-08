<?php

/**
 *
 * @copyright Copyright (c) 2023, RCDevs (info@rcdevs.com)
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
use OCP\AppFramework\Http\TemplateResponse;
use Psr\Log\LoggerInterface;
use OCP\Util;

use OCA\OpenOTPSign\Service\SignService;
use OCP\IConfig;

class SignController extends Controller
{
	private $config;
	private $logger;
	private $signService;
	private $userId;
	private $signEnabled;
	private $signTypeStandard;
	private $signTypeAdvanced;

	public function __construct($AppName, IRequest $request, IConfig $config, SignService $signService, $UserId, LoggerInterface $logger)
	{
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->logger = $logger;
		$this->signService = $signService;
		$this->userId = $UserId;
		// Check enabled OTP
		$this->signEnabled = ($this->config->getAppValue('openotp_sign', 'enable_otp_sign') === '1');
		$this->signTypeStandard = $this->config->getAppValue('openotp_sign', 'sign_type_standard') === '1';
		$this->signTypeAdvanced = $this->config->getAppValue('openotp_sign', 'sign_type_advanced') === '1';
	}

	/**
	 * @NoAdminRequired
	 */
	public function standardSign()
	{
		switch (true) {
			case $this->signEnabled && $this->signTypeStandard:
				$resp = $this->signService->standardSign($this->request->getParam('path'), $this->userId, $this->request->getRemoteAddress());
				break;
			
			case !$this->signEnabled:
				$resp['code'] = false;
				$resp['message'] = 'Sign process is disabled';
				break;
			
			case !$this->signTypeStandard:
				$resp['code'] = false;
				$resp['message'] = 'Cannot sign with disabled Sign type';
				break;
			
			default:
				$resp['code'] = false;
				$resp['message'] = 'Something went wrong during this process';
				break;
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncLocalStandardSign()
	{
		switch (true) {
			case $this->signEnabled && $this->signTypeStandard:
				$resp = $this->signService->asyncLocalStandardSign($this->request->getParam('path'), $this->request->getParam('username'), $this->userId, $this->request->getRemoteAddress(), $this->request->getParam('email'));
				break;
			
			case !$this->signEnabled:
				$resp['code'] = false;
				$resp['message'] = 'Sign process is disabled';
				break;
			
			case !$this->signTypeStandard:
				$resp['code'] = false;
				$resp['message'] = 'Cannot sign with disabled Sign type';
				break;
			
			default:
				$resp['code'] = false;
				$resp['message'] = 'Something went wrong during this process';
				break;
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message'],
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function advancedSign()
	{
		switch (true) {
			case $this->signEnabled && $this->signTypeAdvanced:
				$resp = $this->signService->advancedSign($this->request->getParam('path'), $this->userId, $this->request->getRemoteAddress());
				break;
			
			case !$this->signEnabled:
				$resp['code'] = false;
				$resp['message'] = 'Sign process is disabled';
				break;
			
			case !$this->signTypeAdvanced:
				$resp['code'] = false;
				$resp['message'] = 'Cannot sign with disabled Sign type';
				break;
			
			default:
				$resp['code'] = false;
				$resp['message'] = 'Something went wrong during this process';
				break;
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncLocalAdvancedSign()
	{
		switch (true) {
			case $this->signEnabled && $this->signTypeAdvanced:
				$resp = $this->signService->asyncLocalAdvancedSign($this->request->getParam('path'), $this->request->getParam('username'), $this->userId, $this->request->getRemoteAddress(), $this->request->getParam('email'));
				break;
			
			case !$this->signEnabled:
				$resp['code'] = false;
				$resp['message'] = 'Sign process is disabled';
				break;
			
			case !$this->signTypeAdvanced:
				$resp['code'] = false;
				$resp['message'] = 'Cannot sign with disabled Sign type';
				break;
			
			default:
				$resp['code'] = false;
				$resp['message'] = 'Something went wrong during this process';
				break;
		}


		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function seal()
	{
		if ($this->config->getAppValue('openotp_sign', 'enable_otp_seal') === '1') {
			$resp = $this->signService->seal($this->request->getParam('path'), $this->userId, $this->request->getRemoteAddress());
		} else {
			$resp['code'] = false;
			$resp['message'] = 'Seal process is disabled';
		}

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function cancelSignRequest()
	{
		$resp = $this->signService->cancelSignRequest($this->request->getParam('session'), $this->userId);

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getLocalUsers()
	{
		$cm = \OC::$server->getContactsManager();

		// The API is not active -> nothing to do
		if (!$cm->isEnabled()) {
			$this->logger->error('Contact Manager not enabled');
			return new JSONResponse();
		}

		$result = $cm->search($this->request->getParam('searchQuery'), array('FN', 'EMAIL'));

		$contacts = array();
		foreach ($result as $raw_contact) {
			$contact = array();
			$contact['uid'] = $raw_contact['UID'];
			$contact['display_name'] = $raw_contact['FN'];

			if (array_key_exists('EMAIL', $raw_contact)) {
				$contact['email'] = $raw_contact['EMAIL'][0];
			}

			array_push($contacts, $contact);
		}

		return new JSONResponse($contacts);
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		Util::addScript('openotp_sign', 'openotp_sign-index');
		return new TemplateResponse('openotp_sign', 'index');
	}
}
