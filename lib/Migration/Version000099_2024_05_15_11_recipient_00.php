<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_recipient_00 extends SimpleMigrationStep
{
    const recipient             = 'recipient';
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
            if (!$table->hasColumn(self::recipient)) {
                $table->addColumn(self::recipient, 'string', [
                    'notnull' => true,
                    'length' => 100
                ]);
            } else {
                $table->modifyColumn(self::recipient, [
                    'type'      => Type::getType('string'),
                    'length'    => 100,
                    'notnull'   => true,
                ]);
            }
        }

        return $schema;
    }
}
