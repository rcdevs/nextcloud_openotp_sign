<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_applicant_id_01 extends SimpleMigrationStep
{
    const applicant_id          = 'applicant_id';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_applicant_id      = 'tmp_applicant_id';
    const uid                   = 'uid';

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
                // Add 'applicant_id' column if needed, otherwise modify it
                if (!$table->hasColumn(self::applicant_id)) {
                    $table->addColumn(self::applicant_id, 'string', [
                        'length'    => 256,
                        'notnull'   => true,
                    ]);
                } else {
                    $table->modifyColumn(self::applicant_id, [
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
        // Add previously saved data inside FINAL table (from TMP)
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
                ->set(self::applicant_id, self::tmp_applicant_id);
                //
            ;
            $update->executeStatement();
        }
    }
}
