<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_applicant_id_00 extends SimpleMigrationStep
{
    const uid                   = 'uid';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_applicant_id      = 'tmp_applicant_id';

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

            // Proceed only if old column [uid] exists
            if ($table->hasColumn(self::uid)) {
                // Add TMP column if needed, otherwise modify it
                if (!$table->hasColumn(self::tmp_applicant_id)) {
                    $table->addColumn(self::tmp_applicant_id, 'string', [
                        'notnull' => true,
                        'length' => 256
                    ]);
                } else {
                    $table->modifyColumn(self::tmp_applicant_id, [
                        'type'      => Type::getType('string'),
                        'length'    => 256,
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

        // Proceed only if old column [uid] exists
        if ($table->hasColumn(self::uid)) {
            $update = $this->connection->getQueryBuilder();
            $update
                ->update(self::openotp_sign_sessions)
                ->set(self::tmp_applicant_id, self::uid);
                //
            ;
            $update->executeStatement();
        }
    }
}
