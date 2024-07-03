<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_expiry_date_01 extends SimpleMigrationStep
{
    const id                    = 'id';
    const expiry_date           = 'expiry_date';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_expiration_date   = 'tmp_expiration_date';

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

            // Add 'expiry_date' column if needed, otherwise modify it
            if (!$table->hasColumn(self::expiry_date)) {
                $table->addColumn(self::expiry_date, 'bigint', [
                    'length'    => 20,
                    'unsigned'  => true,
                    'notnull'   => false,
                ]);
            } else {
                $table->modifyColumn(self::expiry_date, [
                    'type'      => Type::getType('bigint'),
                    'length'    => 20,
                    'unsigned'  => true,
                    'notnull'   => false,
                ]);
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

        if ($table->hasColumn(self::tmp_expiration_date)) {
            $query = $this->connection->getQueryBuilder();
            $query->select('*')
                ->from(self::openotp_sign_sessions);
    
            $update = $this->connection->getQueryBuilder();
            $update
                ->update(self::openotp_sign_sessions)
                ->set(self::expiry_date, $update->createParameter(self::expiry_date))
                //
            ;
    
            $result = $query->executeQuery();
    
            // Update all dates with U format
            while ($row = $result->fetch()) {
                $update
                    ->setParameter(self::expiry_date, $row[self::tmp_expiration_date])
                    ->where($update->expr()->eq(self::id, $update->createNamedParameter($row[self::id])))
                    //
                ;
                $update->executeStatement();
            }
        }
    }
}
