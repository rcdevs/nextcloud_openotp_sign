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
use OCP\AppFramework\Http\TemplateResponse;
use Psr\Log\LoggerInterface;
use OCP\Util;

use OCA\OpenOTPSign\Service\SignService;

class SignController extends Controller {
	private $userId;
	private $signService;
	private $logger;

	public function __construct($AppName, IRequest $request, SignService $signService, $UserId, LoggerInterface $logger)
	{
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->signService = $signService;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 */
	public function advancedSign() {
		$resp = $this->signService->advancedSign($this->request->getParam('path'), $this->userId, $this->request->getRemoteAddress());

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncLocalAdvancedSign() {
		$resp = $this->signService->asyncLocalAdvancedSign($this->request->getParam('path'), $this->request->getParam('username'), $this->userId, $this->request->getRemoteAddress(), $this->request->getParam('email'));

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message'],
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncExternalAdvancedSign() {
		$resp = $this->signService->asyncExternalAdvancedSign($this->request->getParam('path'), $this->request->getParam('email'), $this->userId, $this->request->getRemoteAddress());

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message'],
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function qualifiedSign() {
		$resp = $this->signService->qualifiedSign($this->request->getParam('path'), $this->userId, $this->request->getRemoteAddress());

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncLocalQualifiedSign() {
		$resp = $this->signService->asyncLocalQualifiedSign($this->request->getParam('path'), $this->request->getParam('username'), $this->userId, $this->request->getRemoteAddress(), $this->request->getParam('email'));

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncExternalQualifiedSign() {
		$resp = $this->signService->asyncExternalQualifiedSign($this->request->getParam('path'), $this->request->getParam('email'), $this->userId, $this->request->getRemoteAddress());

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function seal() {
		$resp = $this->signService->seal($this->request->getParam('path'), $this->userId, $this->request->getRemoteAddress());

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getLocalUsers() {
		$cm = \OC::$server->getContactsManager();

		// The API is not active -> nothing to do
		if (!$cm->isEnabled()) {
			$this->logger->error('Contact Manager not enabled');
			return new JSONResponse();
		}

		$result = $cm->search($this->request->getParam('searchQuery'), array('FN', 'EMAIL'));

		$contacts = array();
		foreach ($result as $raw_contact){
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
	public function index() {
		Util::addScript('openotp_sign', 'openotp_sign-index');
		return new TemplateResponse('openotp_sign', 'index');
	}
}
