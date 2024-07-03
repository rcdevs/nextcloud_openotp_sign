<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_global_status_02 extends SimpleMigrationStep
{
    const is_error              = 'is_error';
    const is_pending            = 'is_pending';
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

            // Drop 'is_error' column if needed
            if ($table->hasColumn(self::is_error)) {
                $table->dropColumn(self::is_error);
            }

            // Drop 'is_pending' column if needed
            if ($table->hasColumn(self::is_pending)) {
                $table->dropColumn(self::is_pending);
            }
        }

        return $schema;
    }
}
