<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_expiry_date_02 extends SimpleMigrationStep
{
    const expiration_date       = 'expiration_date';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_expiration_date   = 'tmp_expiration_date';

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

            // Drop 'tmp_expiration_date' column if needed
            if ($table->hasColumn(self::tmp_expiration_date)) {
                $table->dropColumn(self::tmp_expiration_date);
            }

            // Drop 'path' column if needed
            if ($table->hasColumn(self::expiration_date)) {
                $table->dropColumn(self::expiration_date);
            }
        }

        return $schema;
    }
}
