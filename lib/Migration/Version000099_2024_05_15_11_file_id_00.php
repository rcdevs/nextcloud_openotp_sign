<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_file_id_00 extends SimpleMigrationStep
{
	const openotp_sign_sessions = 'openotp_sign_sessions';
	const file_id			   = 'file_id';

	private bool $columnExists;

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
			if (!$table->hasColumn(self::file_id)) {
				$table->addColumn(self::file_id, 'bigint', [
					'length'	=> 20,
					'unsigned'  => true,
					'notnull'   => false,
				]);
			} else {
				$table->modifyColumn(self::file_id, [
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
		// Add empty data inside column
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::openotp_sign_sessions)) {
			throw new Exception("Table openotp_sign_sessions is missing", 1);;
		}

		$update = $this->connection->getQueryBuilder();
		$update
			->update(self::openotp_sign_sessions)
			->set(self::file_id, $update->createParameter(self::file_id))
			->setParameter(self::file_id, 0)
			//
		;
		$update->executeStatement();
	}
}
