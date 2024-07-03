<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_advanced_00 extends SimpleMigrationStep
{
    const is_advanced           = 'is_advanced';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_advanced          = 'tmp_advanced';

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

            // Proceed only if old column [is_advanced] exists
            if ($table->hasColumn(self::is_advanced)) {
                // Add TMP column if needed, otherwise modify it
                if (!$table->hasColumn(self::tmp_advanced)) {
                    $table->addColumn(self::tmp_advanced, 'smallint', [
                        'notnull' => false,
                        'length' => 1
                    ]);
                } else {
                    $table->modifyColumn(self::tmp_advanced, [
                        'type'      => Type::getType('smallint'),
                        'length'    => 1,
                        'notnull'   => false,
                    ]);
                }
            }
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
    {
        // Add existing data inside TMP column
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        if (!$schema->hasTable(self::openotp_sign_sessions)) {
            throw new Exception("Table openotp_sign_sessions is missing", 1);;
        }
        $table = $schema->getTable(self::openotp_sign_sessions);

        // Proceed only if old column [is_advanced] exists
        if ($table->hasColumn(self::is_advanced)) {
            $update = $this->connection->getQueryBuilder();
            $update
                ->update(self::openotp_sign_sessions)
                ->set(self::tmp_advanced, self::is_advanced);
                //
            ;
            $update->executeStatement();
        }
    }
}
