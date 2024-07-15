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

declare(strict_types=1);

namespace OCA\OpenOTPSign;

use OCA\OpenOTPSign\AppInfo\Application;
use OCA\Files\Event\LoadSidebar;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<Event>
 */
class FilesLoader implements IEventListener
{
	protected IInitialState $initialState;
	protected IAppManager $appManager;
	protected Config $config;

	public function __construct(
		IInitialState $initialState,
		IAppManager $appManager,
		Config $config
	) {
		$this->initialState = $initialState;
		$this->appManager = $appManager;
		$this->config = $config;
	}

	public static function register(IEventDispatcher $dispatcher): void
	{
		$dispatcher->addServiceListener(LoadAdditionalScriptsEvent::class, self::class);
		// $dispatcher->addServiceListener(LoadSidebar::class, self::class);
	}

	public function handle(Event $event): void
	{
		if ($event instanceof LoadAdditionalScriptsEvent) {
			$this->handleAdditionalScripts($event);
		}
	}


	private function handleAdditionalScripts(LoadAdditionalScriptsEvent $event): void
	{
		if (!$this->appManager->isEnabledForUser(Application::APP_ID)) {
			return;
		}


		// $this->setupInitialState($server);
		Util::addScript(Application::APP_ID, Application::APP_ID . '-loader');
		Util::addStyle(Application::APP_ID, 'icons');
	}
}
