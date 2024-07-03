<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_advanced_02 extends SimpleMigrationStep
{
	const is_advanced		   = 'is_advanced';
	const openotp_sign_sessions = 'openotp_sign_sessions';
	const tmp_advanced		  = 'tmp_advanced';

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): null|ISchemaWrapper
	{
		// Drop TMP and old columns
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(self::openotp_sign_sessions)) {
			$table = $schema->getTable(self::openotp_sign_sessions);

			// Drop 'tmp_advanced' column if needed
			if ($table->hasColumn(self::tmp_advanced)) {
				$table->dropColumn(self::tmp_advanced);
			}

			// Drop 'is_advanced' column if needed
			if ($table->hasColumn(self::is_advanced)) {
				$table->dropColumn(self::is_advanced);
			}
		}

		return $schema;
	}
}
