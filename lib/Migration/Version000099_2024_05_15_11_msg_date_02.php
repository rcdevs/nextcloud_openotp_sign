<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_msg_date_02 extends SimpleMigrationStep
{
    const msg_date              = 'msg_date';
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
            if (!$table->hasColumn(self::msg_date)) {
                throw new Exception(vsprintf('Column `%s` is missing in table `%s`',[self::msg_date, self::openotp_sign_sessions]), 1);
                
            } else {
                $table->modifyColumn(self::msg_date, [
                    'notnull'   => true,
                ]);
            }
        }

        return $schema;
    }
}
