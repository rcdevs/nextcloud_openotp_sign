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

namespace OCA\OpenOTPSign\Service;

require_once __DIR__ . '/../../vendor/autoload.php';

use DateTime;
use Exception;
use nusoap_client;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\OOtpResponse;
use OCA\OpenOTPSign\Db\SignSession;
use OCA\OpenOTPSign\Db\SignSessionMapper;
use OCA\OpenOTPSign\User;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstConfig;
use OCA\OpenOTPSign\Utils\CstDatabase;
use OCA\OpenOTPSign\Utils\CstEntity;
use OCA\OpenOTPSign\Utils\CstException;
use OCA\OpenOTPSign\Utils\CstFile;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\CstStatus;
use OCA\OpenOTPSign\Utils\Helpers;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use OCP\Accounts\IAccountManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class SignService
{
	const CNX_TIME_OUT = 3;

	// Settings
	private $apiKey;
	private $asyncTimeout;
	private $syncTimeout;
	private $useProxy;

	public function __construct(
		private ConfigurationService $configurationService,
		private IAccountManager $accountManager,
		private IConfig $config,
		private IConfig $systemConfig,
		private IDateTimeFormatter $formatter,
		private IFactory $l10nFactory,
		private IL10N $l,
		private IL10N $l10n,
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private LogRCDevs $logRCDevs,
		private SignSessionMapper $mapper,
		private UserService $currentUser,
		private IFilesMetadataManager $filesMetadataManager,
	) {
		Helpers::$l = $l;

		$this->useProxy			= $config->getAppValue(RCDevsApp::APP_ID, 'use_proxy');

		$this->apiKey			= $config->getAppValue(RCDevsApp::APP_ID, 'api_key');
		$this->asyncTimeout		= (int) $config->getAppValue(RCDevsApp::APP_ID, 'async_timeout') * 86400; // in days * seconds/day
		$this->syncTimeout		= (int) $config->getAppValue(RCDevsApp::APP_ID, 'sync_timeout') * 60;
	}

	/** ******************************************************************************************
	 * PRIVATE
	 ****************************************************************************************** */

	private function commonAsyncLocalSign(User $recipient, string $path, int $fileId, $remoteAddress, bool $advanced): array
	{
		$returned = [];

		try {
			$this->logRCDevs->info(vsprintf('Common asynchronous Local Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			if (is_null($this->currentUser->id)) {
				throw new Exception("User ID error", 1);
			}

			$resp = new  OOtpResponse($this->commonSign($recipient, $path, $fileId, $remoteAddress, asynchronous: true, advanced: $advanced));
			if ($resp->isFailed()) {
				throw new Exception($resp[Constante::request(CstRequest::MESSAGE)]);
			}

			// Retrive Soap client
			$soapClient = $resp->getSoap();

			if (!$resp->isCode(2)) {
				throw new Exception("At this step, returned code should be 2");
			} else {
				$dateFormat = 'Y-m-d H:i:s';
				$now = new \DateTime();
				$expiryDate = new \DateTime();
				$expiryDate->add(new \DateInterval('PT' . $this->asyncTimeout . 'S'));
				$overwriteDocument = ($this->configurationService->doyouOverwrite() ? 1 : 0);

				$signSession = new SignSession();
				$signSession->setRecipient(json_encode($recipient));
				$signSession->setCreated(strtotime($now->format($dateFormat)));
				$signSession->setSession($resp->getSession());
				$signSession->setMessage($now->format($dateFormat));
				$signSession->setAdvanced(($advanced ? 1 : 0));
				$signSession->setApplicantId($this->currentUser->id);
				$signSession->setChangeStatus(strtotime($now->format($dateFormat)));
				$signSession->setExpiryDate(strtotime($expiryDate->format($dateFormat)));
				$signSession->setFileId($fileId);
				$signSession->setFilePath($path);
				$signSession->setGlobalStatus(Constante::status(CstStatus::PENDING));
				$signSession->setMsgDate(strtotime($now->format($dateFormat)));
				$signSession->setMutex(null);
				$signSession->setOverwrite($overwriteDocument);

				$this->mapper->insert($signSession);

				// Generate and send QR Code
				$resp = new OOtpResponse(
					$soapClient->call(($advanced ? 'openotpTouchSign' : 'openotpTouchConfirm'), array(
						'session' => $resp->getSession(),
						'sendPush' => false, // False will not send push notification but an email by WebADM
						'qrFormat' => 'PNG',
						'qrSizing' => 5,
						'qrMargin' => 3
					), 'urn:openotp', '', false, null, 'rpc', 'literal')
				);
			}

			$returned = [
				Constante::request(CstRequest::CODE)	=> $resp->getCode(),
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> sprintf('Transaction created for %s', $recipient->displayName),
			];
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

	private function commonSign(User $recipient, string $path, int $fileId, $remoteAddress, bool $asynchronous, bool $toSeal = false, bool $advanced = false): array
	{
		$returned = [];

		try {
			$this->logRCDevs->info(vsprintf('Common Standard Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			if (is_null($this->currentUser->id)) {
				throw new Exception("User ID error", 1);
			}

			$fileToSign = new FileService($this->configurationService, $this->filesMetadataManager, $this->logRCDevs, $this->currentUser, $fileId, toSeal: false);

			if ($this->configurationService->isEnabledDemoMode() && !Helpers::isPdf($path)) {
				throw new Exception('Demo mode enabled. It is only possible to sign PDF files', 1);
			}

			/** @var ProxyService @proxy */
			$proxy = $this->configurationService->proxy();

			$data = vsprintf('<div style="color: white;"><strong>Name: </strong>%s<br><strong>Size: </strong>%s<br><strong>Modified: </strong>%s</div>', [
				$fileToSign->getName(),
				Helpers::humanFileSize($fileToSign->getSize()),
				date('m/d/Y H:i:s', $fileToSign->getMTime()),
			]);

			$nbServers = count($this->configurationService->serversUrls());
			for ($cptServers = 0; $cptServers < $nbServers; ++$cptServers) {
				if (empty($this->configurationService->serversUrls()[$cptServers])) {
					$this->logRCDevs->info(sprintf("This server url is empty, ignoed (#%s)", $cptServers), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
					$resp = new OOtpResponse([]);
					$client = new nusoap_client($this->configurationService->serversUrls()[$cptServers], false, $proxy->host, $proxy->port, $proxy->username, $proxy->password, self::CNX_TIME_OUT, $this->syncTimeout);
				} else {
					$fileB64 = base64_encode($fileToSign->getContent());

					$client = new nusoap_client($this->configurationService->serversUrls()[$cptServers], false, $proxy->host, $proxy->port, $proxy->username, $proxy->password, self::CNX_TIME_OUT, $this->syncTimeout);
					$client->setDebugLevel(0);
					$client->soap_defencoding = 'UTF-8';
					$client->decode_utf8 = FALSE;

					$client->setUseCurl(true);
					$client->setCurlOption(CURLOPT_HTTPHEADER, [
						"Content-type: text/xml;charset=\"utf-8\"",
						"WA-API-Key: {$this->apiKey}",
					]);

					if ($toSeal) {
						$resp = new OOtpResponse($client->call('openotpSeal', array(
							'file' => $fileB64,
							'mode' => '',
							'client' => $this->configurationService->clientId(),
							'source' => $remoteAddress,
						), 'urn:openotp', '', false, null, 'rpc', 'literal'));
					} else {
						if ($advanced) {
							$resp = new OOtpResponse($client->call('openotpNormalSign', array(
								'username' => $recipient->username,
								'data' => $data,
								'file' => $fileB64,
								'mode' => '',
								'async' => $asynchronous,
								'timeout' => ($asynchronous ? $this->asyncTimeout : $this->syncTimeout),
								'issuer' => $this->currentUser->account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
								'client' => $this->configurationService->clientId(),
								'source' => $remoteAddress,
								'virtual' => null
							), 'urn:openotp', '', false, null, 'rpc', 'literal'));
						} else {
							$resp = new OOtpResponse($client->call('openotpNormalConfirm', array(
								'username' => $recipient->username,
								'data' => $data,
								'file' => $fileB64,
								'form' => null,
								'async' => $asynchronous,
								'timeout' => $this->configurationService->timeout($asynchronous),
								'issuer' => $this->currentUser->account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
								'client' => $this->configurationService->clientId(),
								'source' => $remoteAddress,
								'virtual' => null
							), 'urn:openotp', '', false, null, 'rpc', 'literal'));
						}
					}

					if ($client->fault) {
						throw new Exception($resp->getFaultcode() . ' / ' . $resp->getFaultstring());
					}

					$err = $client->getError();
					if ($err) {
						throw new Exception($err, 1);
					}

					break;
				}
			}

			$errMsg = vsprintf(
				'%s%s',
				[
					$resp->getMessage(),
					(is_null($resp->getComment()) ? '' : sprintf(' / %s', $resp->getComment()))
				]
			);

			$message = ($resp->isFailed() ? $errMsg : $resp->getMessage());

			$resp->setSoap($client);

			$returned = $resp->getArray();
			$returned[Constante::request(CstRequest::ERROR)] = $errMsg;
			$returned[Constante::request(CstRequest::MESSAGE)] = $message;
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

	private function commonSyncSign(User $recipient, string $path, int $fileId, $remoteAddress, bool $advanced): array
	{
		$returned = [];

		try {
			$this->logRCDevs->info(vsprintf('Common synchronous Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			if (is_null($this->currentUser->id)) {
				throw new Exception("User ID error", 1);
			}

			$data = [];

			$resp = new OOtpResponse($this->commonSign($recipient, $path, $fileId, $remoteAddress, asynchronous: false, advanced: $advanced));
			if ($resp->isFailed()) {
				throw new Exception($resp->getMessage());
			}

			if (!$resp->isFailed()) {
				/** @var File $createdFile */
				$fileToSign = new FileService($this->configurationService, $this->filesMetadataManager, $this->logRCDevs, $this->currentUser, $fileId, toSeal: false);
				$createdFile = $fileToSign->create(base64_decode($resp->getFile()), $this->configurationService->doyouOverwrite());

				$data = [
					Constante::entity(CstEntity::NAME)		=> ltrim($createdFile->getInternalPath(), 'files/'),
					Constante::entity(CstEntity::FILE_ID)	=> $createdFile->getId(),
					Constante::entity(CstEntity::OVERWRITE)	=> $this->configurationService->doyouOverwrite(),
					Constante::file(CstFile::SIZE)			=> $createdFile->getSize(),
					Constante::file(CstFile::PATH)			=> $createdFile->getPath(),
					Constante::file(CstFile::INTERNAL_PATH)	=> $createdFile->getInternalPath(),
				];
			}

			$this->logRCDevs->debug(vsprintf('Returned data : [%s]', [json_encode($data)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $resp->getMessage(),
			];
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

	private function getUserLocalesTimestamp(string $userId, \DateTime $date)
	{
		$owner = $this->userManager->get($userId);
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
		$date->setTimezone($timeZone);
		return $date->format('Y-m-d H:i:s');
	}

	/** ******************************************************************************************
	 * PUBLIC
	 ****************************************************************************************** */

	public function advancedSign(User $recipient, string $path, int $fileId, $remoteAddress)
	{
		$this->logRCDevs->info(vsprintf('Advanced Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
		return $this->commonSyncSign($recipient, $path, $fileId, $remoteAddress, advanced: true);
	}

	public function asyncLocalAdvancedSign(User $recipient, string $path, int $fileId, $remoteAddress)
	{
		$this->logRCDevs->info(vsprintf('Asynchronous Local Standard Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
		return $this->commonAsyncLocalSign($recipient, $path, $fileId, $remoteAddress, advanced: true);
	}

	public function asyncLocalStandardSign(User $recipient, string $path, int $fileId, $remoteAddress)
	{
		$this->logRCDevs->info(vsprintf('Asynchronous Local Standard Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
		return $this->commonAsyncLocalSign($recipient, $path, $fileId, $remoteAddress, advanced: false);
	}

	public function cancelSignRequest(string $session, string $userId, $forceDeletion = false): array
	{
		$returned = [];

		try {
			// Not needed to contact OTP server if $forceDeletion is true: it means we just delete Issue from the Nextcloud database
			if ($forceDeletion) {
				// Just delete the DB record
				// Change status with "camcelled"
				$resp = $this->mapper->deleteTransaction($session, $userId);
				$returned = $resp;
			} else {
				// Cancel OTP process
				$signSession = $this->mapper->findTransaction($session, applicantId: $userId);

				if (is_null($this->currentUser->id)) {
					throw new Exception("User ID error", 1);
				}

				/** @var ProxyService @proxy */
				$proxy = $this->configurationService->proxy();

				$nbServers = count($this->configurationService->serversUrls());
				for ($cptServers = 0; $cptServers < $nbServers; ++$cptServers) {
					if (empty($this->configurationService->serversUrls()[$cptServers])) {
						$this->logRCDevs->info(sprintf("This server url is empty, ignoed (#%s)", $cptServers), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
						$resp = new OOtpResponse([]);
					} else {
						$client = new nusoap_client($this->configurationService->serversUrls()[$cptServers], false, $proxy->host, $proxy->port, $proxy->username, $proxy->password, self::CNX_TIME_OUT, $this->syncTimeout);
						$client->setDebugLevel(0);
						$client->soap_defencoding = 'UTF-8';
						$client->decode_utf8 = FALSE;

						$client->setUseCurl(true);
						$client->setCurlOption(CURLOPT_HTTPHEADER, [
							"Content-type: text/xml;charset=\"utf-8\"",
							"WA-API-Key: {$this->apiKey}",
						]);

						$resp = new  OOtpResponse($client->call(
							// $operation,
							(Helpers::isAdvanced($signSession->getAdvanced()) ? 'openotpCancelSign' : 'openotpCancelConfirm'),
							array($session),
							'urn:openotp',
							'',
							false,
							null,
							'rpc',
							'literal'
						));

						if ($client->fault) {
							throw new Exception($resp->getFaultcode() . ' / ' . $resp->getFaultstring());
						}

						$err = $client->getError();
						if ($err) {
							throw new Exception($err, 1);
						}

						// if (Helpers::isValidResponse($resp)) {
						if (!$resp->isFailed()) {
							// Change status to camcelled
							$signSession->setChangeStatus(intval(time()));
							$signSession->setGlobalStatus(Constante::status(CstStatus::CANCELLED));
							$signSession->setMessage($resp->getMessage());
							$this->mapper->update($signSession);
						}

						break;
					}
				}

				$errMsg = vsprintf(
					'%s%s',
					[
						$resp->getMessage(),
						(is_null($resp->getComment()) ? '' : sprintf(' / %s', $resp->getComment()))
					]
				);

				$message = ($resp->isFailed() ? $errMsg : $resp->getMessage());
				$returned = $resp->getArray();
			}

			// $returned[Constante::request(CstRequest::CODE)] = intval($returned[Constante::request(CstRequest::CODE)]);
			$returned[Constante::request(CstRequest::ERROR)] = $errMsg;
			$returned[Constante::request(CstRequest::MESSAGE)] = $message;
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

	public function checkAsyncSignature(string|null $userId = null)
	{
		$returned = [];

		try {
			$this->logRCDevs->info(vsprintf('Checking async signatures', []), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$threadId = bin2hex(random_bytes(8));
			$data = [];
			$message = '';

			/** @var ProxyService @proxy */
			$proxy = $this->configurationService->proxy();

			$nbServers = count($this->configurationService->serversUrls());
			for ($cptServers = 0; $cptServers < $nbServers; ++$cptServers) {
				if (empty($this->configurationService->serversUrls()[$cptServers])) {
					$this->logRCDevs->info(sprintf("This server url is empty, ignoed (#%s)", $cptServers), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
				} else {
					$client = new nusoap_client($this->configurationService->serversUrls()[$cptServers], false, $proxy->host, $proxy->port, $proxy->username, $proxy->password, self::CNX_TIME_OUT);

					$client->setDebugLevel(0);
					$client->soap_defencoding = 'UTF-8';
					$client->decode_utf8 = FALSE;

					$client->setUseCurl(true);
					$client->setCurlOption(CURLOPT_HTTPHEADER, [
						"Content-type: text/xml;charset=\"utf-8\"",
						"WA-API-Key: {$this->apiKey}",
					]);

					// Get all pending transactions to check (no filter at all)
					$signSessions = $this->mapper->findPendingsAll($userId, ignoreExpiryDate: true);

					/** @var SignSession  $signSession */
					foreach ($signSessions as $signSession) {
						/**
						 * This "try catch" is needed in case of exception during process: it permits to reset the mutex.
						 * Without this step, we can have deadlocks with not empty mutex for non processed row in database.
						 * The result is that the row will never be treated by this async function.
						 */
						try {
							// Set Mutex to prevent multiple processes for the same session
							$this->mapper->updateTransactionsMutex($threadId, $signSession->getSession());

							// Set current signSession Mutex to "we don't care" value: it will force update because Nextcloud/Doctrine update has a bug...
							$signSession->setMutex('-1');

							// Check if the Mutex === thread Id; if OK, this thread will save the file (if no exception or sth else...)
							$oOtpSignSession = $this->mapper->findTransaction($signSession->getSession(), $signSession->getApplicantId());

							$this->logRCDevs->debug(sprintf('Thread is %s', $threadId), __FUNCTION__);
							$this->logRCDevs->debug(sprintf('Mutex  is %s', $oOtpSignSession->getMutex()), __FUNCTION__);
							if ($oOtpSignSession->getMutex() === $threadId) {
								// Initialize the user ID according to the intel from DB table openotp_sign_sessions
								$this->currentUser->setUserId($signSession->getApplicantId());

								// Check is transaction is an advanced signature or standard
								if (Helpers::isAdvanced($signSession->getAdvanced())) {
									$resp = $client->call('openotpCheckSign',		array($signSession->getSession()), 'urn:openotp', '', false, null, 'rpc', 'literal');
								} else {
									$resp = $client->call('openotpCheckConfirm',	array($signSession->getSession()), 'urn:openotp', '', false, null, 'rpc', 'literal');
								}

								if ($client->fault) {
									throw new Exception($resp['faultcode'] . ' / ' . $resp['faultstring'], 1);
								}

								$err = $client->getError();
								if ($err) {
									throw new Exception($err, 1);
								}

								if (Helpers::isValidResponse($resp)) {
									$path = $signSession->getFilePath();

									// Will be used to keep PDF extension or to switch to P7S extension
									$changeExtension = !Helpers::isPdf($path);

									$fileToSign = new FileService(
										$this->configurationService,
										$this->filesMetadataManager,
										$this->logRCDevs,
										$this->currentUser,
										$signSession->getFileId(),
										toSeal: false,
										changeExtension: $changeExtension
									);

									// This "overwrite" comes from the transaction saved in DB openotp_sign_sessions table; this is not the Config's one
									$doyouOverwrite = ($signSession->isOverwrite());

									$createdFile = $fileToSign->create(base64_decode($resp['file']), $doyouOverwrite);

									$data[] = [
										Constante::entity(CstEntity::NAME)		=> ltrim($createdFile->getInternalPath(), 'files/'),
										Constante::entity(CstEntity::FILE_ID)	=> $createdFile->getId(),
										Constante::entity(CstEntity::OVERWRITE)	=> $this->configurationService->doyouOverwrite(),
										Constante::file(CstFile::SIZE)			=> $createdFile->getSize(),
										Constante::file(CstFile::PATH)			=> $createdFile->getPath(),
										Constante::file(CstFile::INTERNAL_PATH)	=> $createdFile->getInternalPath(),
									];

									// Delete the signed transaction from DB (useless to keep it)
									$this->mapper->delete($signSession);
								} else if ($resp[Constante::request(CstRequest::CODE)] === '0') {
									$signSession->setGlobalStatus(Constante::status(CstStatus::ERROR));
									$signSession->setMessage($resp[Constante::entity(CstEntity::MESSAGE)]);
									// Update Status in DB
									$this->mapper->update($signSession);
								} else {
									$signSession->setMutex(null);
									// Update Status in DB
									$this->mapper->update($signSession);
								}

								$message = $resp[Constante::request(CstRequest::MESSAGE)];
							}
						} catch (\Throwable $th) {
							$signSession->setMutex(null);
							$this->mapper->update($signSession);
							throw $th;
						}
					}
				}
			}

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $message,
			];
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

	// Get the OTP server url status (OK/KO) for the Settings page
	public function checkSettings(int $serverIndex)
	{
		$returned = [];

		try {
			$data = [];
			$message = null;

			// Retrieve connection intel from database
			$apiKey		 = $this->config->getAppValue(RCDevsApp::APP_ID, 'api_key');
			$serversUrls	= json_decode($this->config->getAppValue(RCDevsApp::APP_ID, 'servers_urls'), true);

			$serverUrl = $serversUrls[$serverIndex];

			// If server url is empty, not needed to check it => N/A which will return [-1]
			if (empty($serverUrl)) {
				$returned = [
					Constante::request(CstRequest::CODE)	=> -1,
					Constante::request(CstRequest::DATA)	=> null,
					Constante::request(CstRequest::ERROR)	=> null,
					Constante::request(CstRequest::MESSAGE)	=> 'N/A : Empty server URL',
				];
			} else {
				// Call the server
				$soapClient = new SoapService($this->configurationService, $this->logRCDevs, $serverUrl);
				$resp = $soapClient->openOtpStatus();

				$message = $resp[Constante::request(CstRequest::MESSAGE)];

				$returned = [
					Constante::request(CstRequest::CODE)	=> 1,
					Constante::request(CstRequest::DATA)	=> $data,
					Constante::request(CstRequest::ERROR)	=> null,
					Constante::request(CstRequest::MESSAGE)	=> $message,
				];
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(vsprintf('Checking Settings process failed for server #%d [%s]', [$serverIndex, $th->getMessage()]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	public function getServersNumber(): array
	{
		$returned = [];

		try {
			// Get servers from Config DB
			$serversUrls = json_decode($this->config->getAppValue(RCDevsApp::APP_ID, 'servers_urls'), true);

			$data = [];

			if (is_array($serversUrls)) {
				$data = [
					Constante::config(CstConfig::SERVERS_NUMBER) => count($serversUrls),
				];
			} else {
				throw new Exception(Constante::exception(CstException::NOT_SERVERS_ARRAY), 1);
			}

			$returned = [
				Constante::request(CstRequest::CODE)		=> 1,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::DATA)		=> $data,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)		=> 0,
				Constante::request(CstRequest::ERROR)	=> $th->getMessage(),
				Constante::request(CstRequest::DATA)		=> null,
			];
		}

		return $returned;
	}

	public function lastRunJob()
	{
		$returned = [];

		try {
			$code = 0;
			$data = [];
			$error = null;
			$message = null;

			$resp = $this->mapper->findJob();
			if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
				throw new Exception($resp[Constante::request(CstRequest::ERROR)], 1);
			}

			$reservedAt = Helpers::getArrayData($resp[Constante::request(CstRequest::DATA)], 'reserved_at', true, 'No "Reservation" column found in query result');
			$lastRun 	= Helpers::getArrayData($resp[Constante::request(CstRequest::DATA)], 'last_run', true, 'No "Last Run" column found in query result');
			$data = [
				Constante::database(CstDatabase::COLUMN_LAST_RUN)		=> $lastRun,
				Constante::database(CstDatabase::COLUMN_RESERVED_AT)	=> $reservedAt,
			];

			switch (true) {
				case $reservedAt === 0 && $lastRun === 0:
					$code = 1;
					$message = 'The cron job is activated';
					break;

				case $reservedAt === 0 && $lastRun !== 0:
					$code = 1;
					$message = json_encode(['The cron job is activated; the last time the job ran was at %s', date('Y-m-d_H:i:s', $lastRun)]);
					break;

				default:
					$code = 0;
					$error = $message = json_encode(['The cron job was disabled at %s', date('Y-m-d_H:i:s', $reservedAt)]);
					$data = null;
					break;
			}

			$returned = [
				Constante::request(CstRequest::CODE)	=> $code,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> $error,
				Constante::request(CstRequest::MESSAGE)	=> $message,
			];
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf('Checking Last Run Job process failed [%s]', $th->getMessage()), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__);
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	public function resetJob()
	{
		$resp = $this->mapper->resetJob();
		if ($resp[Constante::request(CstRequest::CODE)] === 1) {
			$resp[Constante::request(CstRequest::MESSAGE)] = json_encode(['The cron job has been activated at %s', date('Y-m-d_H:i:s')]);
		}

		return $resp;
	}

	public function seal(User $recipient, string $path, int $fileId, $remoteAddress)
	{
		$returned = [];

		try {
			$this->logRCDevs->info(vsprintf('Seal for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			if (is_null($this->currentUser->id)) {
				throw new Exception("User ID error", 1);
			}

			$data = [];

			$resp = $this->commonSign($recipient, $path, $fileId, $remoteAddress, asynchronous: false, toSeal: true);
			if ($resp[Constante::request(CstRequest::CODE)] !== 1) {
				throw new Exception($resp[Constante::request(CstRequest::MESSAGE)], 1);
			}

			if ($resp[Constante::request(CstRequest::CODE)] === 1) {
				/** @var File $createdFile */
				$fileToSign = new FileService($this->configurationService, $this->filesMetadataManager, $this->logRCDevs, $this->currentUser, $fileId, toSeal: true);
				$createdFile = $fileToSign->create(base64_decode($resp['file']), $this->configurationService->doyouOverwrite());

				$data = [
					Constante::entity(CstEntity::NAME)		=> ltrim($createdFile->getInternalPath(), 'files/'),
					Constante::entity(CstEntity::FILE_ID)	=> $createdFile->getId(),
					Constante::entity(CstEntity::OVERWRITE)	=> $this->configurationService->doyouOverwrite(),
					Constante::file(CstFile::SIZE)			=> $createdFile->getSize(),
					Constante::file(CstFile::PATH)			=> $createdFile->getPath(),
					Constante::file(CstFile::INTERNAL_PATH)	=> $createdFile->getInternalPath(),
				];
			}

			$this->logRCDevs->debug(vsprintf('Returned data : [%s]', [json_encode($data)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $resp[Constante::request(CstRequest::MESSAGE)],
			];
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

	public function standardSign(User $recipient, string $path, int $fileId, $remoteAddress)
	{
		$this->logRCDevs->info(vsprintf('Standard Signature for file #%s: [%s]', [$fileId, json_encode($path)]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
		return $this->commonSyncSign($recipient, $path, $fileId, $remoteAddress, advanced: false);
	}
}
