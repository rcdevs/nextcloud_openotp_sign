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


use OCA\OpenOTPSign\Db\SignSessionMapper;
use OCA\OpenOTPSign\Service\RequestsService;
use OCA\OpenOTPSign\Service\SignService;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstDatabase;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\CstStatus;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class RequestsController extends Controller
{

	public function __construct(
		$AppName,
		IRequest $request,
		private string $userId,
		private SignSessionMapper $mapper,
		private RequestsService $requestsService,
		private SignService $signService,
	) {
		parent::__construct($AppName, $request);
	}

	/** ******************************************************************************************
	 * PRIVATE
	 ****************************************************************************************** */

	private function commonGetListRequests(string $list)
	{
		$returned = [
			Constante::database(CstDatabase::COUNT) => 0,
			Constante::database(CstDatabase::REQUESTS) => [],
		];

		try {
			$page 		= $this->request->getParam(Constante::request(CstRequest::PAGE)) ?? 0;
			$nbItems	= $this->request->getParam(Constante::request(CstRequest::NB_ITEMS));

			if ($nbItems == 0) {
				$nbItems = 20;
			}

			$returned = $this->requestsService->getListRequests($this->userId, $page, $nbItems, pending: strcasecmp($list, Constante::status(CstStatus::PENDING)) === 0);
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)		=> 0,
				Constante::database(CstDatabase::COUNT)		=> 0,
				Constante::database(CstDatabase::REQUESTS)	=> null,
			];
		}
		return $returned;
	}

	/** ******************************************************************************************
	 * PUBLIC
	 ****************************************************************************************** */

	/**
	 * @NoAdminRequired
	 */
	public function getIssuesRequests()
	{
		return new JSONResponse($this->commonGetListRequests(Constante::status(CstStatus::ISSUE)));
	}

	/**
	 * @NoAdminRequired
	 */
	public function getPendingRequests()
	{
		return new JSONResponse($this->commonGetListRequests(Constante::status(CstStatus::PENDING)));
	}
}
