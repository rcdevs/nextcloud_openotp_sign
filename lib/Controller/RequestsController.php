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
	public function getPendingRequests() {

        $pendingRequests = $this->mapper->findPendingsByUid($this->userId);

		return new JSONResponse($pendingRequests);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getCompletedRequests() {

        $pendingRequests = $this->mapper->findCompletedByUid($this->userId);

		return new JSONResponse($pendingRequests);
	}

	/**
	 * @NoAdminRequired
	 */
	public function getFailedRequests() {

        $pendingRequests = $this->mapper->findFailedByUid($this->userId);

		return new JSONResponse($pendingRequests);
	}
}