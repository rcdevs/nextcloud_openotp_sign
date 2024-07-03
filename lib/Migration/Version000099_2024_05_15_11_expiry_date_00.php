<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_expiry_date_00 extends SimpleMigrationStep
{
    const bigint                = 'bigint';
    const expiration_date       = 'expiration_date';
    const expiry_date           = 'expiry_date';
    const id                    = 'id';
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

            // Proceed only if column format is wrong : not [bigint]
            if ($table->hasColumn(self::expiry_date)) {
                $column = $table->getColumn(self::expiry_date);

                if ($column->getType() !== Type::getType(self::bigint)) {
                    // Add TMP column if needed, otherwise modify it
                    if (!$table->hasColumn(self::tmp_expiration_date)) {
                        $table->addColumn(self::tmp_expiration_date, 'bigint', [
                            'notnull' => false,
                            'length' => 20
                        ]);
                    } else {
                        $table->modifyColumn(self::tmp_expiration_date, [
                            'type'      => Type::getType('bigint'),
                            'length'    => 20,
                            'unsigned'  => true,
                            'notnull'   => false,
                        ]);
                    }
                } else { // Migration not needed, remove tmp column if exists, to prevent issue on phase [_02]
                    if ($table->hasColumn(self::tmp_expiration_date)) {
                        $table->dropColumn(self::tmp_expiration_date);
                    }
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

        // Proceed only if column format is wrong : not [bigint]
        if ($table->hasColumn(self::expiry_date)) {
            $column = $table->getColumn(self::expiry_date);

            if (
                $column->getType() !== Type::getType(self::bigint)
                && $table->hasColumn(self::tmp_expiration_date)
            ) {
                $query = $this->connection->getQueryBuilder();
                $query->select('*')
                    ->from(self::openotp_sign_sessions);

                $update = $this->connection->getQueryBuilder();
                $update
                    ->update(self::openotp_sign_sessions)
                    ->set(self::tmp_expiration_date, $update->createParameter(self::tmp_expiration_date))
                    //
                ;

                $result = $query->executeQuery();

                // Update all dates with U format
                while ($row = $result->fetch()) {
                    $update
                        ->setParameter(self::tmp_expiration_date, strtotime($row[self::expiration_date]))
                        ->where($update->expr()->eq(self::id, $update->createNamedParameter($row[self::id])))
                        //
                    ;
                    $update->executeStatement();
                }
            }
        }
    }
}
