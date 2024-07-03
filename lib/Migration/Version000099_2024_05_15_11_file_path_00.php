<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_file_path_00 extends SimpleMigrationStep
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
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable(self::openotp_sign_sessions)) {
            $table = $schema->getTable(self::openotp_sign_sessions);

            // Proceed only if old column [is_advanced] exists
            if ($table->hasColumn(self::path)) {
                // Add TMP column if needed, otherwise modify it
                if (!$table->hasColumn(self::tmp_file_path)) {
                    $table->addColumn(self::tmp_file_path, 'string', [
                        'notnull' => true,
                        'length' => 512
                    ]);
                } else {
                    $table->modifyColumn(self::tmp_file_path, [
                        'type'      => Type::getType('string'),
                        'length'    => 512,
                        'notnull'   => true,
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
        if ($table->hasColumn(self::path)) {
            $update = $this->connection->getQueryBuilder();
            $update
                ->update(self::openotp_sign_sessions)
                ->set(self::tmp_file_path, self::path);
                //
            ;
            $update->executeStatement();
        }
    }
}
