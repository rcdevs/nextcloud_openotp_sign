<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_change_status_02 extends SimpleMigrationStep
{
    const change_status         = 'change_status';
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
            
            // enable NOT NULL
            if (!$table->hasColumn(self::change_status)) {
                throw new Exception(vsprintf('Column `%s` is missing in table `%s`',[self::change_status, self::openotp_sign_sessions]), 1);
                
            } else {
                $table->modifyColumn(self::change_status, [
                    'notnull'   => true,
                ]);
            }
        }

        return $schema;
    }
}
