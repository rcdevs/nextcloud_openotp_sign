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

use OCA\OpenOTPSign\Service\SignService;

class SignController extends Controller {
	private $userId;
	private $signService;

	public function __construct($AppName, IRequest $request, SignService $signService, $UserId)
	{
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->signService = $signService;
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
	public function asyncAdvancedSign() {
		$resp = $this->signService->asyncAdvancedSign($this->request->getParam('path'), $this->request->getParam('username'), $this->userId, $this->request->getRemoteAddress());

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
	public function asyncQualifiedSign() {
		$resp = $this->signService->asyncQualifiedSign($this->request->getParam('path'), $this->request->getParam('username'), $this->userId, $this->request->getRemoteAddress());

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
}
