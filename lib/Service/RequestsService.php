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

use OCA\OpenOTPSign\Db\SignSessionMapper;
use OCA\OpenOTPSign\User;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstDatabase;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\Helpers;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use OCP\Accounts\IAccountManager;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class RequestsService
{
	const CNX_TIME_OUT = 3;

	public function __construct(
		private IAccountManager $accountManager,
		private IConfig $config,
		private IL10N $l,
		private IRootFolder $storage,
		private IUserManager $userManager,
		private SignSessionMapper $mapper,
		private IFactory $l10nFactory,
		private IConfig $systemConfig,
		private IL10N $l10n,
		private IDateTimeFormatter $formatter,
		private LogRCDevs $logRCDevs,
		private SignService $signService,
		private $UserId,
	) {
		Helpers::$l = $l;
	}

	public function getListRequests(string $userId, int $page, int $nbItems, bool $pending = true)
	{
		$rightNow = intval(time());

		try {
			if ($pending) {
				// Send the backgroung job once (so, not on issue function)
				$this->signService->checkAsyncSignature($userId);

				// Now the pendings list
				$count = $this->mapper->countPendingsByApplicant($rightNow, $userId);
				$databaseResponse = $this->mapper->findPendingsByApplicant($rightNow, $userId, page: $page, nbItems: $nbItems);
			} else {
				// Issues list
				$count = $this->mapper->countIssuesByApplicant($userId);
				$databaseResponse = $this->mapper->findIssuesByApplicant($userId, $page, $nbItems);
			}

			$requests = [];
			foreach ($databaseResponse as $databaseRecord) {
				/**@var User $user */
				$user = json_decode($databaseRecord->getRecipient(), false);
				
				$requests[] = [
					'id'			=> $databaseRecord->getId(),
					'created'		=> $databaseRecord->getCreated(),
					'expiry_date'	=> $databaseRecord->getExpiryDate(),
					'recipient'		=> $user->displayName,
					'file_path'		=> basename($databaseRecord->getFilePath()),
					'session'		=> $databaseRecord->getSession(),
					'global_status'	=> $databaseRecord->getGlobalStatus(),
				];
			}

			$returned = [
				Constante::request(CstRequest::CODE)		=> 1,
				Constante::database(CstDatabase::COUNT)		=> $count,
				Constante::database(CstDatabase::REQUESTS)	=> $requests,
			];
		} catch (\Throwable $th) {
			$this->logRCDevs->error($th->getMessage(), __FUNCTION__);
			$returned = [
				Constante::request(CstRequest::CODE)		=> 0,
				Constante::database(CstDatabase::COUNT)		=> 0,
				Constante::database(CstDatabase::REQUESTS)	=> [],
			];
		}

		return $returned;
	}
}
