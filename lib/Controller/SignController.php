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

use Exception;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Db\SignSessionMapper;
use OCA\OpenOTPSign\Service\ConfigurationService;
use OCA\OpenOTPSign\Service\SignService;
use OCA\OpenOTPSign\User;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstCommon;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\CstOOtpSign;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Util;
use Psr\Log\LoggerInterface;

class SignController extends Controller
{
	private string $currentUserId;
	// private string|null $userEmail;
	private string|null $userName;
	private User $currentUser;

	/** @var IUserManager */
	private $userManager;

	public function __construct(
		$AppName,
		$UserId,
		IRequest $request,
		IUserManager $userManager,
		private ConfigurationService $configurationService,
		private ISearch $search,
		private IURLGenerator $urlGenerator,
		private IUserSession $userSession,
		private LoggerInterface $logger,
		private LogRCDevs $logRCDevs,
		private SignService $signService,
		private SignSessionMapper $mapper,
	) {
		$this->request = $request;
		parent::__construct($AppName, $request);
		$this->currentUserId = $UserId;

		$this->userManager = $userManager;

		$this->userName = $UserId;
		// // Get user email
		// $this->userEmail = $this->userManager->get($this->currentUserId)->getEMailAddress();
		// // If standard email is empty and userId is an email, use userId
		// if (empty($this->userEmail) && filter_var($this->currentUserId, FILTER_VALIDATE_EMAIL)) {
		// 	$this->userEmail = $this->currentUserId;
		// }

		// Define "Sender name" which will be displayed on OTP push/email : "You received a signature request from ..."
		$displayName = $this->userManager->get($this->currentUserId)->getDisplayName();
		if (empty($displayName)) {
			$displayName = $this->$this->currentUserId;
		}

		$this->currentUser = new User($this->userName, $displayName);
	}

	/**
	 * @NoAdminRequired
	 */
	public function advancedSign()
	{
		$returned = [];

		try {
			switch (true) {
				case $this->configurationService->isEnabledSign() && $this->configurationService->isEnabledSignTypeAdvanced():
					$resp = $this->signService->advancedSign(
						$this->currentUser,
						$this->request->getParam('path'),
						$this->request->getParam('fileId'),
						$this->request->getRemoteAddress()
					);
					if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
						throw new Exception($resp[Constante::request(CstRequest::MESSAGE)], 1);
					}

					$returned = [
						Constante::request(CstRequest::CODE)	=> 1,
						Constante::request(CstRequest::DATA)	=> $resp[Constante::request(CstRequest::DATA)],
						Constante::request(CstRequest::ERROR)	=> null,
						Constante::request(CstRequest::MESSAGE)	=> $resp[Constante::request(CstRequest::MESSAGE)],
					];

					break;

				case !$this->configurationService->isEnabledSign():
					throw new Exception('Sign process is disabled', 1);
					break;

				case !$this->configurationService->isEnabledSignTypeStandard():
					throw new Exception('Cannot sign with disabled Sign type', 1);
					break;

				default:
					throw new Exception('Something went wrong during this process', 1);
					break;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Critical error during process. Error is \"%s\"", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncLocalAdvancedSign()
	{
		$returned = [];

		try {
			switch (true) {
				case $this->configurationService->isEnabledSign() && $this->configurationService->isEnabledSignTypeAdvanced():
					// Get data from request
					$resp = $this->signService->asyncLocalAdvancedSign(
						new User(
							$this->request->getParam('recipientId'),
							$this->request->getParam('recipientName'),
						),
						$this->request->getParam('path'),
						$this->request->getParam('fileId'),
						$this->request->getRemoteAddress()
					);
					if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
						throw new Exception($resp[Constante::request(CstRequest::MESSAGE)], 1);
					}

					$returned = [
						Constante::request(CstRequest::CODE)	=> $resp[Constante::request(CstRequest::CODE)],
						Constante::request(CstRequest::DATA)	=> $resp[Constante::request(CstRequest::DATA)],
						Constante::request(CstRequest::ERROR)	=> null,
						Constante::request(CstRequest::MESSAGE)	=> $resp[Constante::request(CstRequest::MESSAGE)],
					];

					break;

				case !$this->configurationService->isEnabledSign():
					throw new Exception('Sign process is disabled', 1);
					break;

				case !$this->configurationService->isEnabledSignTypeStandard():
					throw new Exception('Cannot sign with disabled Sign type', 1);
					break;

				default:
					throw new Exception('Something went wrong during this process', 1);
					break;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Critical error during process. Error is \"%s\"", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	/**
	 * @NoAdminRequired
	 */
	public function asyncLocalStandardSign()
	{
		$returned = [];

		try {
			switch (true) {
				case $this->configurationService->isEnabledSign() && $this->configurationService->isEnabledSignTypeStandard():
					// Get data from request
					$resp = $this->signService->asyncLocalStandardSign(
						new User(
							$this->request->getParam('recipientId'),
							$this->request->getParam('recipientName'),
						),
						$this->request->getParam('path'),
						$this->request->getParam('fileId'),
						$this->request->getRemoteAddress()
					);
					if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
						throw new Exception($resp[Constante::request(CstRequest::MESSAGE)], 1);
					}

					$returned = [
						Constante::request(CstRequest::CODE)	=> $resp[Constante::request(CstRequest::CODE)],
						Constante::request(CstRequest::DATA)	=> $resp[Constante::request(CstRequest::DATA)],
						Constante::request(CstRequest::ERROR)	=> null,
						Constante::request(CstRequest::MESSAGE)	=> $resp[Constante::request(CstRequest::MESSAGE)],
					];

					break;

				case !$this->configurationService->isEnabledSign():
					throw new Exception('Sign process is disabled', 1);
					break;

				case !$this->configurationService->isEnabledSignTypeStandard():
					throw new Exception('Cannot sign with disabled Sign type', 1);
					break;

				default:
					throw new Exception('Something went wrong during this process', 1);
					break;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Critical error during process. Error is \"%s\"", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	/**
	 * @NoAdminRequired
	 */
	public function cancelSignRequest()
	{
		$resp = $this->signService->cancelSignRequest($this->request->getParam('session'), $this->currentUserId);

		return new JSONResponse([
			'code' => $resp['code'],
			'message' => $resp['message']
		]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function forceDeletion()
	{
		$resp = $this->signService->cancelSignRequest($this->request->getParam('session'), $this->currentUserId, forceDeletion: true);

		return new JSONResponse([
			'code' => strval($resp['code']),
			'message' => $resp['message']
		]);
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *		  required and no CSRF check. If you don't know what CSRF is, read
	 *		  it up in the docs or you might create a security hole. This is
	 *		  basically the only required method to add this exemption, don't
	 *		  add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		Util::addScript(RCDevsApp::APP_ID, 'openotp_sign-index');
		return new TemplateResponse(RCDevsApp::APP_ID, 'index');
	}

	/**
	 * @NoAdminRequired
	 */
	public function seal()
	{
		$returned = [];

		try {
			switch (true) {
				case $this->configurationService->isEnabledSeal():
					$resp = $this->signService->seal(
						$this->currentUser,
						$this->request->getParam('path'),
						$this->request->getParam('fileId'),
						$this->request->getRemoteAddress()
					);
					if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
						throw new Exception($resp[Constante::request(CstRequest::MESSAGE)], 1);
					}

					$returned = $resp;
					break;

				case !$this->configurationService->isEnabledSign():
					throw new Exception('Seal process is disabled', 1);
					break;

				default:
					throw new Exception('Something went wrong during this process', 1);
					break;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Critical error during process. Error is \"%s\"", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	/**
	 * @NoAdminRequired
	 */
	public function standardSign()
	{
		$returned = [];

		try {
			switch (true) {
				case $this->configurationService->isEnabledSign() && $this->configurationService->isEnabledSignTypeStandard():
					$resp = $this->signService->standardSign(
						$this->currentUser,
						$this->request->getParam('path'),
						$this->request->getParam('fileId'),
						$this->request->getRemoteAddress()
					);
					if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
						throw new Exception($resp[Constante::request(CstRequest::MESSAGE)], 1);
					}

					$returned = $resp;
					break;

				case !$this->configurationService->isEnabledSign():
					throw new Exception('Sign process is disabled', 1);
					break;

				case !$this->configurationService->isEnabledSignTypeStandard():
					throw new Exception('Cannot sign with disabled Sign type', 1);
					break;

				default:
					throw new Exception('Something went wrong during this process', 1);
					break;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Critical error during process. Error is \"%s\"", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}
}
