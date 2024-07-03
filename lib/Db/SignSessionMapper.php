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

namespace OCA\OpenOTPSign\Db;

use OC\SystemConfig;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstDatabase;
use OCA\OpenOTPSign\Utils\CstEntity;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\CstStatus;
use OCA\OpenOTPSign\Utils\Helpers;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class SignSessionMapper extends QBMapper
{
	public int $maxItems = 50;
	public int $backInTime = 3600; // Use to find users' last activity and to reduce the number of retrieved transactions
	private string $tablesPrefix;
	private string $tableAlias = 'ymsSess';
	private string $nxcToken = '';

	public function __construct(
		IDBConnection $db,
		private LogRCDevs $logRCDevs,
	) {
		parent::__construct(
			$db,
			RCDevsApp::APP_NAMETABLE_SESSIONS,
			SignSession::class
		);
		$this->db = $db;
		$this->tablesPrefix = \OC::$server->get(SystemConfig::class)->getValue('dbtableprefix', 'oc_');
	}

	/**
	 * Query completions
	 */
	private function commonSetParameter(array|null $unitTransactionToUpdate, string $paramName, IQueryBuilder &$queryBuilder)
	{
		if (
			!is_null($unitTransactionToUpdate) &&
			array_key_exists($paramName, $unitTransactionToUpdate) &&
			!is_null($unitTransactionToUpdate[$paramName])
		)
			$queryBuilder
				->set($paramName, $queryBuilder->createParameter($paramName))
				->setParameter($paramName, $unitTransactionToUpdate[$paramName]);
	}

	private function setApplicantIdIfExists(array|null $unitTransactionToUpdate, IQueryBuilder &$queryBuilder)
	{
		$this->commonSetParameter($unitTransactionToUpdate, Constante::entity(CstEntity::APPLICANT_ID), $queryBuilder);
	}

	private function setEnvelopeIdIfExists(array|null $unitTransactionToUpdate, IQueryBuilder &$queryBuilder)
	{
		$this->commonSetParameter($unitTransactionToUpdate, Constante::entity(CstEntity::SESSION), $queryBuilder);
	}

	private function setStatusIfExists(array|null $unitTransactionToUpdate, IQueryBuilder &$queryBuilder)
	{
		$this->commonSetParameter($unitTransactionToUpdate, Constante::entity(CstEntity::STATUS), $queryBuilder);
	}

	private function setGlobalStatusIfExists(array|null $unitTransactionToUpdate, IQueryBuilder &$queryBuilder)
	{
		$this->commonSetParameter($unitTransactionToUpdate, Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder);
	}

	private function whereApplicantIfExists(string|null $applicantId, IQueryBuilder &$queryBuilder)
	{
		if (!is_null($applicantId)) {
			$queryBuilder->andWhere($queryBuilder->expr()->eq('applicant_id', $queryBuilder->createNamedParameter($applicantId, IQueryBuilder::PARAM_STR)));
		}
	}

	private function whereRecipientIfExists(string|null $recipient, IQueryBuilder &$queryBuilder)
	{
		if (!is_null($recipient)) {
			$queryBuilder->andWhere($queryBuilder->expr()->eq('recipient', $queryBuilder->createNamedParameter($recipient, IQueryBuilder::PARAM_STR)));
		}
	}

	private function whereSessionIfExists(string|null $session, IQueryBuilder &$queryBuilder)
	{
		if (!is_null($session)) {
			$queryBuilder->andWhere($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session, IQueryBuilder::PARAM_STR)));
		}
	}

	private function whereGlobalStatusActive(IQueryBuilder &$queryBuilder)
	{
		// AND condition
		$queryBuilder->andWhere(
			$queryBuilder->expr()->andX(
				$queryBuilder->expr()->neq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::CANCELLED))),
				$queryBuilder->expr()->neq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::NOT_APPLICABLE))),
				$queryBuilder->expr()->neq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::NOT_FOUND))),
				$queryBuilder->expr()->neq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::SIGNED))),
			)
		);
	}

	private function whereGlobalStatusIssues(IQueryBuilder &$queryBuilder)
	{
		// OR condition
		$queryBuilder->andWhere(
			$queryBuilder->expr()->andX(
				$queryBuilder->expr()->neq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::PENDING))),
				$queryBuilder->expr()->neq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::SIGNED))),
			)
		);
	}

	private function whereGlobalStatusPending(IQueryBuilder &$queryBuilder)
	{
		// OR condition
		$queryBuilder->andWhere(
			$queryBuilder->expr()->eq(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createNamedParameter(Constante::status(CstStatus::PENDING))),
		);
	}

	private function whereExpiryDate(int $rightNow, IQueryBuilder &$queryBuilder)
	{
		$queryBuilder->andWhere($queryBuilder->expr()->gte(Constante::entity(CstEntity::EXPIRY_DATE), $queryBuilder->createNamedParameter(strval($rightNow), IQueryBuilder::PARAM_INT)));
	}

	private function joinActivity(IQueryBuilder &$queryBuilder)
	{
		$queryBuilder->join(
			$this->tableAlias,
			'authtoken',
			$this->nxcToken,
			'uid = applicant_id',
		);
	}

	private function whereLastActivity(int $rightNow, IQueryBuilder &$queryBuilder)
	{
		$queryBuilder->andWhere('nxcToken.last_activity >= :paramLastActivityBackInTime')
			->setParameter('paramLastActivityBackInTime', intval($rightNow) - $this->backInTime, IQueryBuilder::PARAM_INT);
	}

	private function whereChangeStatus(int $rightNow, IQueryBuilder &$queryBuilder)
	{
		$queryBuilder->andWhere($queryBuilder->expr()->lt(Constante::entity(CstEntity::CHANGE_STATUS), $queryBuilder->createNamedParameter(strval($rightNow), IQueryBuilder::PARAM_INT)));
	}

	/**
	 * DB Ops
	 */

	public function countTransactions(int $rightNow, string $applicantId = null): array
	{
		$returned = [];

		try {
			$data = [];
			$message = null;

			/** @var IResult $result */
			/** @var IQueryBuilder $queryBuilder */

			$queryBuilder = $this->db->getQueryBuilder();

			$queryBuilder->selectAlias($queryBuilder->createFunction('COUNT(*)'), Constante::database(CstDatabase::COUNT))
				->from($this->getTableName(), $this->tableAlias)
				//
			;

			// Add more filters to prevent huge data
			$this->joinActivity($queryBuilder);

			$this->whereApplicantIfExists($applicantId, $queryBuilder);
			$this->whereLastActivity($rightNow, $queryBuilder);
			$this->whereGlobalStatusActive($queryBuilder);
			$this->whereExpiryDate($rightNow, $queryBuilder);
			$this->whereChangeStatus($rightNow, $queryBuilder);

			$result = $queryBuilder->executeQuery();
			$data = $count = $result->fetchOne();
			$result->closeCursor();

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $message,
			];
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf('Query building failed [%s]', $th->getMessage()), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__);
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	public function countIssuesByApplicant(string $applicantId)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select($queryBuilder->createFunction('COUNT(*)'))
			->from($this->getTableName());

		// Add set parameters
		$this->whereApplicantIfExists($applicantId, $queryBuilder);
		$this->whereGlobalStatusIssues($queryBuilder);

		$result = $queryBuilder->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	public function countIssuesByEnvelopeId(string $session)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select($queryBuilder->createFunction('COUNT(*)'))
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)))
			->andWhere(
				$queryBuilder->expr()->orX(
					$queryBuilder->expr()->eq('global_status', $queryBuilder->createNamedParameter(Constante::status(CstStatus::DECLINED))),
					$queryBuilder->expr()->eq('global_status', $queryBuilder->createNamedParameter(Constante::status(CstStatus::CANCELLED))),
					$queryBuilder->expr()->eq('global_status', $queryBuilder->createNamedParameter(Constante::status(CstStatus::EXPIRED))),
				)
			);

		$result = $queryBuilder->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	public function countTransactionsByEnvelopeId(string $session)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select($queryBuilder->createFunction('COUNT(*)'))
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)));

		$result = $queryBuilder->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	public function deleteTransaction(string $session, string $applicant): array
	{
		$returned = [];

		try {
			$data = [];

			$queryBuilder = $this->db->getQueryBuilder();

			$queryBuilder->delete($this->getTableName());

			$this->whereApplicantIfExists($applicant, $queryBuilder);
			$this->whereSessionIfExists($session, $queryBuilder);

			$data[Constante::request(CstRequest::NB_ITEMS)] = $queryBuilder->executeStatement();

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::DATA)	=> $data,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::ERROR)	=> $th->getMessage(),
				Constante::request(CstRequest::DATA)	=> null,
			];
		}

		return $returned;
	}

	public function deleteTransactionByApplicant(string $session, string $applicant)
	{
		$queryBuilder = $this->db->getQueryBuilder();
		$queryBuilder->delete($this->getTableName())
			->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)))
			->andWhere($queryBuilder->expr()->eq('applicant_id', $queryBuilder->createNamedParameter($applicant)));

		$queryBuilder->executeStatement();
	}

	public function findActiveTransaction(string $session, string $recipient = '')
	{
		$queryBuilder = $this->db->getQueryBuilder();
		$queryBuilder->selectDistinct(
			['applicant_id', 'workspace_id', 'workflow_id', 'session', 'global_status']
		)
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)))
			->andWhere(
				$queryBuilder->expr()->orX(
					$queryBuilder->expr()->eq('global_status', $queryBuilder->createNamedParameter(Constante::status(CstStatus::APPROVED))),
					$queryBuilder->expr()->eq('global_status', $queryBuilder->createNamedParameter(Constante::status(CstStatus::STARTED))),
				)
			);

		if ($recipient !== '') $queryBuilder->andWhere($queryBuilder->expr()->eq('recipient', $queryBuilder->createNamedParameter($recipient)));

		return $this->findEntity($queryBuilder);
	}

	public function findIssuesByApplicant(string $applicantId, int $page = 0, int $nbItems = 20)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select(
			'advanced',
			'applicant_id',
			'change_status',
			'created',
			'expiry_date',
			'file_id',
			'file_path',
			'global_status',
			'id',
			'message',
			'msg_date',
			'mutex',
			'recipient',
			'session',
		)
			->from($this->getTableName(), $this->tableAlias)
			// Add order by to display on WebUI
			->orderBy('change_status', 'desc')
			->addOrderBy('created', 'desc')
			->addOrderBy('recipient', 'asc')
			//
		;

		// Add more filters
		$this->whereApplicantIfExists($applicantId, $queryBuilder);
		$this->whereGlobalStatusIssues($queryBuilder);

		$queryBuilder->setFirstResult($page * $nbItems);
		$queryBuilder->setMaxResults($nbItems);

		return $this->findEntities($queryBuilder);
	}

	public function findJob()
	{
		$returned = [];

		try {
			$data = [];

			$queryBuilder = $this->db->getQueryBuilder();

			$queryBuilder->select(
				Constante::database(CstDatabase::COLUMN_RESERVED_AT),
				Constante::database(CstDatabase::COLUMN_LAST_RUN),
			)
				->from(Constante::database(CstDatabase::TABLE_JOBS))
				->where($queryBuilder->expr()->eq(
					Constante::database(CstDatabase::COLUMN_CLASS),
					$queryBuilder->createNamedParameter('OCA\OpenOTPSign\BackgroundJob\CheckAsyncSignatureTask')
				));

			$cursor = $queryBuilder->executeQuery();

			$data = $cursor->fetch();
			if (!$data) { // No row in database => not an error, the cron just has never run
				$data = [
					Constante::database(CstDatabase::COLUMN_RESERVED_AT)	=> 0,
					Constante::database(CstDatabase::COLUMN_LAST_RUN)		=> 0,
				];
			}
			$cursor->closeCursor();

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::DATA)	=> $data,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::ERROR)	=> $th->getMessage(),
				Constante::request(CstRequest::DATA)	=> null,
			];
		}

		return $returned;
	}

	public function findAll(int $page = -1, int $nbItems = -1): array
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select('id', 'applicant_id', 'file_path', 'workspace_id', 'workflow_id', 'workflow_name', 'session', 'status', 'expiry_date', 'created', 'change_status', 'recipient', 'global_status', 'msg_date', 'file_id')
			->from($this->getTableName())
			->orderBy('change_status', 'desc')
			->addOrderBy('created', 'desc');

		if ($page !== -1 && $nbItems !== -1) {
			$queryBuilder->setFirstResult($page * $nbItems);
			$queryBuilder->setMaxResults($nbItems);
		}

		return $this->findEntities($queryBuilder);
	}

	public function findAllEnvelopesIds(int $rightNow, string $applicantId = null, int $page = -1, int $nbItems = -1): array
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select('applicant_id', 'session')
			->from($this->getTableName(), $this->tableAlias)
			// Add order by to display on WebUI
			->orderBy('change_status', 'desc')
			->addOrderBy('created', 'desc')
			//
		;

		// Junctions
		$this->joinActivity($queryBuilder);

		// Add more filters to prevent huge data
		$this->whereApplicantIfExists($applicantId, $queryBuilder);
		$this->whereLastActivity($rightNow, $queryBuilder);
		$this->whereGlobalStatusActive($queryBuilder);
		$this->whereExpiryDate($rightNow, $queryBuilder);
		$this->whereChangeStatus($rightNow, $queryBuilder);

		// Set pages
		if ($page !== -1 && $nbItems !== -1) {
			$queryBuilder->setFirstResult($page * $nbItems);
			$queryBuilder->setMaxResults($nbItems);
		}

		return $this->findEntities($queryBuilder);
	}

	// public function findAllActiveEnvelopesIds(string $applicantId = null, int $limit = null)
	// {
	// 	$queryBuilder = $this->db->getQueryBuilder();

	// 	$queryBuilder->select('id', 'applicant_id', 'file_path', 'workspace_id', 'session')
	// 		->from($this->getTableName())
	// 		->setMaxResults($limit)
	// 		->orderBy('change_status', 'desc')
	// 		->addOrderBy('created', 'desc')
	// 		->where(
	// 			$queryBuilder->expr()->neq('global_status', $queryBuilder->createNamedParameter(Constante::cst(CstCommon::ARCHIVED)))
	// 		)
	// 		->andWhere($queryBuilder->expr()->neq('global_status', $queryBuilder->createNamedParameter(Constante::status(CstStatus::NOT_APPLICABLE))));

	// 	if (!is_null($applicantId)) {
	// 		$queryBuilder->andWhere($queryBuilder->expr()->eq('applicant_id', $queryBuilder->createNamedParameter($applicantId)));
	// 	}

	// 	return $this->findEntities($queryBuilder);
	// }

	public function countPendingsByApplicant(int $rightNow, string $applicantId)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select($queryBuilder->createFunction('COUNT(*)'))
			->from($this->getTableName())
			//
		;

		// Add filters
		$this->whereApplicantIfExists($applicantId, $queryBuilder);
		$this->whereGlobalStatusPending($queryBuilder);
		$this->whereExpiryDate($rightNow, $queryBuilder);

		$result = $queryBuilder->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		return $count;
	}


	public function findPendingsByApplicant(int $rightNow, string|null $applicantId, bool $ignoreExpiryDate = false, int $page = 0, int $nbItems = 20): array
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select(
			'advanced',
			'applicant_id',
			'change_status',
			'created',
			'expiry_date',
			'file_id',
			'file_path',
			'global_status',
			'id',
			'message',
			'msg_date',
			'mutex',
			'recipient',
			'session',
		)
			->from($this->getTableName(), $this->tableAlias)
			// Add order by to display on WebUI
			->orderBy('change_status', 'desc')
			->addOrderBy('created', 'desc')
			//
		;

		// Add filters
		$this->whereApplicantIfExists($applicantId, $queryBuilder);
		$this->whereGlobalStatusPending($queryBuilder);
		if (!$ignoreExpiryDate) {
			$this->whereExpiryDate($rightNow, $queryBuilder);
		}

		$queryBuilder->setFirstResult($page * $nbItems);
		$queryBuilder->setMaxResults($nbItems);

		return $this->findEntities($queryBuilder);
	}

	public function findPendingsAll(string|null $applicantId, bool $ignoreExpiryDate = false): array
	{
		return $this->findPendingsByApplicant(intval(time()), $applicantId, ignoreExpiryDate: $ignoreExpiryDate, nbItems: 1000);
	}

	public function findRecipientTransaction(string $session, string $recipient)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select('*')
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('session',		$queryBuilder->createNamedParameter($session)))
			->andWhere($queryBuilder->expr()->eq('recipient',	$queryBuilder->createNamedParameter($recipient)));

		return $this->findEntity($queryBuilder);
	}

	public function findTransaction(string $session, string|null $applicantId = null)
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select('*')
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)))
			->setMaxResults(1);

		// if ($recipient !== '') $queryBuilder->andWhere($queryBuilder->expr()->eq('recipient', $queryBuilder->createNamedParameter($recipient)));

		// Add filters
		$this->whereApplicantIfExists($applicantId, $queryBuilder);

		return $this->findEntity($queryBuilder);
	}

	public function findTransactions(string $session = '', string $applicant = ''): array
	{
		$queryBuilder = $this->db->getQueryBuilder();

		$queryBuilder->select('*')
			->from($this->getTableName());

		$whereCommand = 'where';

		if ($session !== '') {
			$queryBuilder->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)));
			$whereCommand = 'andWhere';
		}
		if ($applicant !== '') {
			$queryBuilder->$whereCommand($queryBuilder->expr()->eq('applicant_id', $queryBuilder->createNamedParameter($applicant)));
		}

		return $this->findEntities($queryBuilder);
	}

	public function resetJob()
	{
		$returned = [];

		try {
			$data = [];

			$queryBuilder = $this->db->getQueryBuilder();

			$queryBuilder->update(Constante::database(CstDatabase::TABLE_JOBS))
				->set(
					Constante::database(CstDatabase::COLUMN_RESERVED_AT),
					$queryBuilder->createParameter(Constante::database(CstDatabase::COLUMN_RESERVED_AT))
				)
				->setParameter(
					Constante::database(CstDatabase::COLUMN_RESERVED_AT),
					0
				)
				->where(
					$queryBuilder->expr()->like(
						Constante::database(CstDatabase::COLUMN_CLASS),
						$queryBuilder->createNamedParameter(
							'%' . $this->db->escapeLikeParameter('\OpenOTPSign\\') . '%'
						)
					)
				);

			$updatedRows = $queryBuilder->executeStatement();

			$data = [
				Constante::database(CstDatabase::QRY_UPDATED_ROWS) => $updatedRows,
			];

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::DATA)	=> $data,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::ERROR)	=> $th->getMessage(),
				Constante::request(CstRequest::DATA)	=> null,
			];
		}

		return $returned;
	}

	/**
	 * UPDATES
	 */
	public function updateTransactionAllStatuses(string $session, string $newStatus)
	{
		try {
			$queryBuilder = $this->db->getQueryBuilder();
			$queryBuilder->update($this->getTableName())
				->set('global_status', $queryBuilder->createParameter('global_status'))
				->setParameter('global_status', $newStatus)
				->set('status', $queryBuilder->createParameter('status'))
				->setParameter('status', $newStatus)
				->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)));
			$queryBuilder->executeStatement();
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function updateTransactionStatus(string $session, string $newStatus)
	{
		$queryBuilder = $this->db->getQueryBuilder();
		$queryBuilder->update($this->getTableName())
			->set('status', $queryBuilder->createParameter('status'))
			->setParameter('status', $newStatus)
			->where($queryBuilder->expr()->eq('session', $queryBuilder->createNamedParameter($session)));
		$queryBuilder->executeStatement();
	}

	public function updateTransactionsStatus(array $transactionsToUpdate): int
	{
		$realTransactionUpdated = 0;
		try {
			foreach ($transactionsToUpdate as $unitTransactionToUpdate) {
				$queryBuilder = $this->db->getQueryBuilder();
				$queryBuilder->update($this->getTableName())
					->set(Constante::entity(CstEntity::CHANGE_STATUS), $queryBuilder->createParameter(Constante::entity(CstEntity::CHANGE_STATUS)))
					->setParameter(Constante::entity(CstEntity::CHANGE_STATUS), time())

					->where($queryBuilder->expr()->eq(Constante::entity(CstEntity::SESSION), $queryBuilder->createNamedParameter($unitTransactionToUpdate[Constante::entity(CstEntity::SESSION)])));

				// Add set parameters
				$this->setStatusIfExists($unitTransactionToUpdate, $queryBuilder);
				$this->setGlobalStatusIfExists($unitTransactionToUpdate, $queryBuilder);


				$this->whereApplicantIfExists(Helpers::getIfExists(Constante::entity(CstEntity::APPLICANT_ID), $unitTransactionToUpdate), $queryBuilder);

				$this->whereRecipientIfExists(Helpers::getIfExists(Constante::entity(CstEntity::RECIPIENT), $unitTransactionToUpdate), $queryBuilder);

				$queryBuilder->executeStatement();
				$realTransactionUpdated++;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Exception during updating status with message: %s", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			throw $th;
		}

		return $realTransactionUpdated;
	}

	public function updateTransactionsStatusExpired(): void
	{
		try {
			$queryBuilder = $this->db->getQueryBuilder();
			$queryBuilder->update($this->getTableName())
				// status
				->set(Constante::entity(CstEntity::STATUS), $queryBuilder->createParameter(Constante::entity(CstEntity::STATUS)))
				->setParameter(Constante::entity(CstEntity::STATUS), Constante::status(CstStatus::EXPIRED))
				// global status
				->set(Constante::entity(CstEntity::GLOBAL_STATUS), $queryBuilder->createParameter(Constante::entity(CstEntity::GLOBAL_STATUS)))
				->setParameter(Constante::entity(CstEntity::GLOBAL_STATUS), Constante::status(CstStatus::EXPIRED))
				// change_status
				->set(Constante::entity(CstEntity::CHANGE_STATUS), $queryBuilder->createParameter(Constante::entity(CstEntity::CHANGE_STATUS)))
				->setParameter(Constante::entity(CstEntity::CHANGE_STATUS), time())

				->where($queryBuilder->expr()->lt(Constante::entity(CstEntity::EXPIRY_DATE), $queryBuilder->createNamedParameter(intval(time()))));

			$queryBuilder->executeStatement();
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Exception during updating status with message: %s", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			throw $th;
		}
	}

	public function updateTransactionsMutex(string $threadId, string $session): void
	{
		// Not needed to use transactional query
		try {
			$queryBuilder = $this->db->getQueryBuilder();
			$queryBuilder->select('*')
				->from($this->getTableName())
				->where($queryBuilder->expr()->eq(Constante::entity(CstEntity::SESSION), $queryBuilder->createNamedParameter($session)))
				// ->andWhere($queryBuilder->expr()->eq(Constante::entity(CstEntity::MUTEX), $queryBuilder->createNamedParameter('')))
				->andWhere($queryBuilder->expr()->isNull(Constante::entity(CstEntity::MUTEX)))
				//
				;

			$resp = $this->findEntities($queryBuilder);

			$queryBuilder = $this->db->getQueryBuilder();
			$queryBuilder->update($this->getTableName())
				// mutex
				->set(Constante::entity(CstEntity::MUTEX), $queryBuilder->createParameter(Constante::entity(CstEntity::MUTEX)))
				->setParameter(Constante::entity(CstEntity::MUTEX), $threadId)

				->where($queryBuilder->expr()->eq(Constante::entity(CstEntity::SESSION), $queryBuilder->createNamedParameter($session)))
				// ->andWhere($queryBuilder->expr()->eq(Constante::entity(CstEntity::MUTEX), $queryBuilder->createNamedParameter('')))
				->andWhere($queryBuilder->expr()->isNull(Constante::entity(CstEntity::MUTEX)))
				//
				;

			$resp = $queryBuilder->executeStatement();

			$this->logRCDevs->debug(sprintf('UPD response : %d', $resp), __FUNCTION__);
		} catch (\Throwable $th) {
			$this->logRCDevs->error(sprintf("Exception during updating status with message: %s", $th->getMessage()), __FUNCTION__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);

			throw $th;
		}
	}
}
