<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_mutex_00 extends SimpleMigrationStep
{
    const mutex                 = 'mutex';
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

            // Add column if needed, otherwise modify it
            if (!$table->hasColumn(self::mutex)) {
                $table->addColumn(self::mutex, 'string', [
                    'length'    => 32,
                    'notnull'   => false,
                ]);
            } else {
                $table->modifyColumn(self::mutex, [
                    'type'      => Type::getType('string'),
                    'length'    => 32,
                    'notnull'   => false,
                ]);
            }
        }

        return $schema;
    }
}
