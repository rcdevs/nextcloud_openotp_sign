<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_created_00 extends SimpleMigrationStep
{
    const bigint                = 'bigint';
    const created               = 'created';
    const id                    = 'id';
    const openotp_sign_sessions = 'openotp_sign_sessions';
    const tmp_created           = 'tmp_created';

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
            if ($table->hasColumn(self::created)) {
                $column = $table->getColumn(self::created);

                if ($column->getType() !== Type::getType(self::bigint)) {
                    // Add TMP column if needed, otherwise modify it
                    if (!$table->hasColumn(self::tmp_created)) {
                        $table->addColumn(self::tmp_created, self::bigint, [
                            'notnull' => false,
                            'length' => 20
                        ]);
                    } else {
                        $table->modifyColumn(self::tmp_created, [
                            'type'      => Type::getType(self::bigint),
                            'length'    => 20,
                            'unsigned'  => true,
                            'notnull'   => false,
                        ]);
                    }
                } else { // Migration not needed, remove tmp column if exists, to prevent issue on phase [_02]
                    if ($table->hasColumn(self::tmp_created)) {
                        $table->dropColumn(self::tmp_created);
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
        if ($table->hasColumn(self::created)) {
            $column = $table->getColumn(self::created);

            if (
                $column->getType() !== Type::getType(self::bigint)
                && $table->hasColumn(self::tmp_created)
            ) {
                $query = $this->connection->getQueryBuilder();
                $query->select('*')
                    ->from(self::openotp_sign_sessions);

                $update = $this->connection->getQueryBuilder();
                $update
                    ->update(self::openotp_sign_sessions)
                    ->set(self::tmp_created, $update->createParameter(self::tmp_created))
                    //
                ;

                $result = $query->executeQuery();

                // Update all dates with U format
                while ($row = $result->fetch()) {
                    $update
                        ->setParameter(self::tmp_created, strtotime($row[self::created]))
                        ->where($update->expr()->eq(self::id, $update->createNamedParameter($row[self::id])))
                        //
                    ;
                    $update->executeStatement();
                }
            }
        }
    }
}
