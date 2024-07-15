<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_06_17_16_overwrite_01 extends SimpleMigrationStep
{
	const overwrite			  = 'overwrite';
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

			// Add 'overwrite' column if needed, otherwise modify it
			if (!$table->hasColumn(self::overwrite)) {
				$table->addColumn(self::overwrite, 'smallint', [
					'length'	=> 1,
					'notnull'   => false,
				]);
			} else {
				$table->modifyColumn(self::overwrite, [
					'type'	  => Type::getType('smallint'),
					'length'	=> 1,
					'notnull'   => false,
				]);
			}
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
	}
}
