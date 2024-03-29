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

use OCA\OpenOTPSign\Db\SignSession;
use OCA\OpenOTPSign\Db\SignSessionMapper;

class RequestsController extends Controller {
	private $userId;
    private $mapper;

	public function __construct($AppName, IRequest $request, $UserId, SignSessionMapper $mapper)
	{
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
        $this->mapper = $mapper;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getPendingRequests(int $page = 0, int $nbItems = 20) {

		$count = $this->mapper->countPendingsByUid($this->userId);
		$requests = $this->mapper->findPendingsByUid($this->userId, $page, $nbItems);

		return new JSONResponse([
			'count' => $count,
			'requests' => $requests,
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getCompletedRequests(int $page = 0, int $nbItems = 20) {

		$count = $this->mapper->countCompletedByUid($this->userId);
		$requests = $this->mapper->findCompletedByUid($this->userId, $page, $nbItems);

		return new JSONResponse([
			'count' => $count,
			'requests' => $requests,
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getFailedRequests(int $page = 0, int $nbItems = 20) {

		$count = $this->mapper->countFailedByUid($this->userId);
		$requests = $this->mapper->findFailedByUid($this->userId, $page, $nbItems);

		return new JSONResponse([
			'count' => $count,
			'requests' => $requests,
		]);
	}
}