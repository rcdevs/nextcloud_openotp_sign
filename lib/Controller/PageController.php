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

namespace OCA\OpenOTPSign\Controller;

use OCA\OpenOTPSign\AppInfo\Application;
use OCA\OpenOTPSign\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\HintException;
use OCP\IRequest;
use OCP\IUserSession;

class PageController extends Controller
{
	private IInitialState $initialState;
	private IUserSession $userSession;
	private Config $config;

	public function __construct(
		string $appName,
		IRequest $request,
		IInitialState $initialState,
		IUserSession $userSession,
		Config $config
	) {
		parent::__construct($appName, $request);
		$this->initialState = $initialState;
		$this->userSession = $userSession;
		$this->config = $config;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 * @throws HintException
	 */
	public function index(): Response
	{
		$response = new TemplateResponse(Application::APP_ID, 'index', [
			'app' => Application::APP_ID,
			'id-app-content' => '#app-content-vue',
			'id-app-navigation' => '#app-navigation-vue',
		]);
		return $response;
	}
}
