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

use OC\AppFramework\Http;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class UserController extends Controller
{
	/** @var IUserManager */

	public function __construct(
		private IAppManager $appManager,
		$AppName,
		IRequest $request,
		private string $userId,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private ISearch $search,
		private IUserSession $userSession
	) {
		parent::__construct($AppName, $request);
	}

	/** ******************************************************************************************
	 * PUBLIC
	 ****************************************************************************************** */

	/**
	 * @NoAdminRequired
	 */
	public function getCurrentUserEmail()
	{
		$returned = '';

		try {
			$user = $this->userManager->get($this->userId);
			$returned = $user->getEMailAddress();
		} catch (\Throwable $th) {
			$returned = '';
		}
		// $user = $this->userSession->getUser();
		return $returned;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getCurrentUserId()
	{
		return $this->userId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getLocalUsers(string $search = '', string $type = ''): DataResponse
	{
		$shareTypes = [];
		switch ($type) {
			case 'user':
				$shareTypes[] = IShare::TYPE_USER;
				break;
			case 'email':
				$shareTypes[] = IShare::TYPE_EMAIL;
				break;
		}
		if (empty($shareTypes)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$minLength = 3;
		if (strlen($search) < $minLength) {
			return new DataResponse([]);
		}

		$limit = 10;
		$offset = 0;
		$lookup = false;	// Don't use lookup server.
		[$result, $hasMoreResults] = $this->search->search($search, $shareTypes, $lookup, $limit, $offset);
		if ($type === 'user') {
			// Filter out users not allowed to use the app.
			$userCache = [];
			$filterFunc = function ($elem) use ($userCache) {
				$userId = $elem['value']['shareWith'];
				$user = $userCache[$userId] ?? null;
				if (!$user) {
					$user = $this->userManager->get($userId);
					if (!$user) {
						return true;
					}
					$userCache[$userId] = $user;
				}
				return $this->appManager->isEnabledForUser(RCDevsApp::APP_ID, $user);
			};

			$totalResults = count($result['exact']['users'] ?? []) + count($result['users'] ?? []);
			if (isset($result['exact']['users'])) {
				$result['exact']['users'] = array_filter($result['exact']['users'], $filterFunc);
			}
			if (isset($result['users'])) {
				$result['users'] = array_filter($result['users'], $filterFunc);
			}
			$total = count($result['exact']['users'] ?? []) + count($result['users'] ?? []);
			while ($totalResults > 0 && $total < $limit && $hasMoreResults) {
				$offset += $limit;
				[$additional, $hasMoreResults] = $this->search->search($search, $shareTypes, $lookup, $limit, $offset);
				$totalResults = count($additional['exact']['users'] ?? []) + count($additional['users'] ?? []);
				if (!$totalResults) {
					break;
				}

				if (isset($additional['exact']['users'])) {
					$additional['exact']['users'] = array_filter($additional['exact']['users'], $filterFunc);
				}
				if (isset($additional['users'])) {
					$additional['users'] = array_filter($additional['users'], $filterFunc);
				}
				$result['exact']['users'] = array_merge($result['exact']['users'], $additional['exact']['users']);
				$result['users'] = array_merge($result['users'], $additional['users']);
				$total = count($result['exact']['users'] ?? []) + count($result['users'] ?? []);
			}
		}
		$response = new DataResponse($result);
		return $response;
	}
}
