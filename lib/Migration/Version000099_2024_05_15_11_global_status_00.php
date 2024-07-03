<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCA\OpenOTPSign\Service\Status;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstStatus;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_global_status_00 extends SimpleMigrationStep
{
	const global_status		 = 'global_status';
	const id					= 'id';
	const is_error			  = 'is_error';
	const is_pending			= 'is_pending';
	const openotp_sign_sessions = 'openotp_sign_sessions';

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): null|ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(self::openotp_sign_sessions)) {
			$table = $schema->getTable(self::openotp_sign_sessions);

			// Add column if needed, otherwise modify it
			if (!$table->hasColumn(self::global_status)) {
				$table->addColumn(self::global_status, 'string', [
					'length'	=> 32,
					'notnull'   => false,
				]);
			} else {
				$table->modifyColumn(self::global_status, [
					'type'	  => Type::getType('string'),
					'length'	=> 32,
					'notnull'   => false,
				]);
			}
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
		// Add existing data inside TMP column
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::openotp_sign_sessions)) {
			throw new Exception("Table openotp_sign_sessions is missing", 1);;
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from(self::openotp_sign_sessions);

		$update = $this->connection->getQueryBuilder();
		$update
			->update(self::openotp_sign_sessions)
			->set(self::global_status, $update->createParameter(self::global_status))
			//
		;

		$result = $query->executeQuery();

		// Update all dates with U format
		while ($row = $result->fetch()) {
			// Choose status to write in column global_status
			$toUpdateGlobalStatus = false;
			$chosenStatus = null;

			// Prepare migration according to existing status (fields is_error & is_pending)
			switch (true) {
				case $row[self::is_error]:
					$toUpdateGlobalStatus = true;
					$chosenStatus = Constante::status(CstStatus::ERROR);
					break;

				case $row[self::is_pending]:
					$toUpdateGlobalStatus = true;
					$chosenStatus = Constante::status(CstStatus::PENDING);
					break;

				default:
					# Nothing to do, no update will be processed
					break;
			}
			if ($toUpdateGlobalStatus) {
				$update
					->setParameter(self::global_status, $chosenStatus)
					->where($update->expr()->eq(self::id, $update->createNamedParameter($row[self::id])))
					//
				;
				$update->executeStatement();
			}
		}
	}
}
