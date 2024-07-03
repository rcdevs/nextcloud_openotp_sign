<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_is_yumisign_02 extends SimpleMigrationStep
{
	const is_yumisign		   = 'is_yumisign';
	const openotp_sign_sessions = 'openotp_sign_sessions';

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): null|ISchemaWrapper
	{
		// Drop old columns
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(self::openotp_sign_sessions)) {
			$table = $schema->getTable(self::openotp_sign_sessions);

			// Drop 'is_yumisign' column if needed
			if ($table->hasColumn(self::is_yumisign)) {
				$table->dropColumn(self::is_yumisign);
			}
		}

		return $schema;
	}
}
