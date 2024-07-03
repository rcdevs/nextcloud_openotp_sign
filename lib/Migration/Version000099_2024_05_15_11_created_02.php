<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_created_02 extends SimpleMigrationStep
{
    const created               = 'created';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_created           = 'tmp_created';

    public function __construct(
        private IDBConnection $connection,
    ) {
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): null|ISchemaWrapper
    {
        // Drop TMP column
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable(self::openotp_sign_sessions)) {
            $table = $schema->getTable(self::openotp_sign_sessions);

            // Drop 'tmp_created' column if needed
            if ($table->hasColumn(self::tmp_created)) {
                $table->dropColumn(self::tmp_created);
            }

            // enable NOT NULL
            if (!$table->hasColumn(self::created)) {
                throw new Exception(vsprintf('Column `%s` is missing in table `%s`', [self::created, self::openotp_sign_sessions]), 1);
            } else {
                $table->modifyColumn(self::created, [
                    'notnull'   => true,
                ]);
            }
        }

        return $schema;
    }
}
