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

use DateTime;
use Exception;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class UserService
{
	public string|null		$id;
	public Folder|null		$folder;
	public IAccount|null	$account;

	public function __construct(
		$UserId,
		private IAccountManager	$accountManager,
		private IConfig			$systemConfig,
		private IFactory		$l10nFactory,
		private IL10N			$l10n,
		private IRootFolder		$rootFolder,
		private IUserManager	$userManager,
	) {
		$this->initUserid((isset($UserId) ? $UserId : null));
	}

	private function initUserid(string|null $userId)
	{
		$this->id		= (is_null($userId)		? null : $userId);
		$user			= (is_null($this->id)	? null : $this->userManager->get($this->id));
		$this->folder	= (is_null($user)		? null : $this->rootFolder->getUserFolder($user->getUID()));
		$this->account	= (is_null($user)		? null : $this->accountManager->getAccount($user));
	}

	public function setUserId(string $userId)
	{
		$this->initUserid($userId);
	}

	public function getTimedLocales(\DateTime $date = new DateTime())
	{
		if (is_null($this->id)) {
			$timeZone = new \DateTimeZone('UTC');
		} else {

			$owner = $this->userManager->get($this->id);
			$lang = 'en';
			$timeZone = $this->systemConfig->getUserValue($owner->getUID(), 'core', 'timezone', null);
			$timeZone = isset($timeZone) ? new \DateTimeZone($timeZone) : new \DateTimeZone('UTC');

			if ($lang) {
				$l10n = $this->l10nFactory->get(RCDevsApp::APP_ID, $lang);
				if (!$l10n) {
					$l10n = $this->l10n;
				}
			} else {
				$l10n = $this->l10n;
			}
		}

		$date->setTimezone($timeZone);
		return $date->format('Y-m-d H:i:s');
	}
}
