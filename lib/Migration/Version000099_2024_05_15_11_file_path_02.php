<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_file_path_02 extends SimpleMigrationStep
{
    const path                  = 'path';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_file_path         = 'tmp_file_path';

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

            // Drop 'tmp_file_path' column if needed
            if ($table->hasColumn(self::tmp_file_path)) {
                $table->dropColumn(self::tmp_file_path);
            }

            // Drop 'path' column if needed
            if ($table->hasColumn(self::path)) {
                $table->dropColumn(self::path);
            }
        }

        return $schema;
    }
}
