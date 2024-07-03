<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_change_status_00 extends SimpleMigrationStep
{
	const created			   = 'created';
	const openotp_sign_sessions = 'openotp_sign_sessions';
	const change_status		 = 'change_status';

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
			if (!$table->hasColumn(self::change_status)) {
				$table->addColumn(self::change_status, 'bigint', [
					'length'	=> 20,
					'unsigned'  => true,
					'notnull'   => false,
				]);
			} else {
				$table->modifyColumn(self::change_status, [
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
		// Add `created` data inside column
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::openotp_sign_sessions)) {
			throw new Exception("Table openotp_sign_sessions is missing", 1);;
		}

		$update = $this->connection->getQueryBuilder();
		$update
			->update(self::openotp_sign_sessions)
			->set(self::change_status, self::created)
			->where($update->expr()->eq(self::change_status, null));
			//
		;
		$update->executeStatement();
	}
}
