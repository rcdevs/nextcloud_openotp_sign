<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_applicant_id_02 extends SimpleMigrationStep
{
	const uid					= 'uid';
	const openotp_sign_sessions  = 'openotp_sign_sessions';
	const tmp_applicant_id	   = 'tmp_applicant_id';
	const openotp_sign_uid_index = 'openotp_sign_uid_index';

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

			// Drop 'tmp_applicant_id' column if needed
			if ($table->hasColumn(self::tmp_applicant_id)) {
				$table->dropColumn(self::tmp_applicant_id);
			}

			// Drop 'uid' column if needed
			if ($table->hasColumn(self::uid)) {
				$table->dropColumn(self::uid);
			}
			if ($table->hasIndex(self::openotp_sign_uid_index)) {
				$table->dropIndex(self::openotp_sign_uid_index);
			}
		}

		return $schema;
	}
}
