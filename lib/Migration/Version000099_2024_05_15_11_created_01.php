<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_created_01 extends SimpleMigrationStep
{
	const id					= 'id';
	const created			   = 'created';
	const openotp_sign_sessions = 'openotp_sign_sessions';
	const tmp_created		   = 'tmp_created';

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

			// Add 'created' column if needed, otherwise modify it
			if (!$table->hasColumn(self::created)) {
				$table->addColumn(self::created, 'bigint', [
					'length'	=> 20,
					'unsigned'  => true,
					'notnull'   => false,
				]);
			} else {
				$table->modifyColumn(self::created, [
					'type'	  => Type::getType('bigint'),
					'length'	=> 20,
					'unsigned'  => true,
					'notnull'   => false,
				]);
			}
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
		// Add previously saved data inside FINAL table (from TMP)
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::openotp_sign_sessions)) {
			throw new Exception("Table openotp_sign_sessions is missing", 1);;
		}

		$table = $schema->getTable(self::openotp_sign_sessions);

		if ($table->hasColumn(self::tmp_created)) {
			$query = $this->connection->getQueryBuilder();
			$query->select('*')
				->from(self::openotp_sign_sessions);

			$update = $this->connection->getQueryBuilder();
			$update
				->update(self::openotp_sign_sessions)
				->set(self::created, $update->createParameter(self::created))
				//
			;

			$result = $query->executeQuery();

			// Update all dates with U format
			while ($row = $result->fetch()) {
				$update
					->setParameter(self::created, $row[self::tmp_created])
					->where($update->expr()->eq(self::id, $update->createNamedParameter($row[self::id])))
					//
				;
				$update->executeStatement();
			}
		}
	}
}
