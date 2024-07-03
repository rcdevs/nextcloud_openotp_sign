<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000004Date20230727170000 extends SimpleMigrationStep
{

    private $db;

    public function __construct(IDBConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
    {
        $query = $this->db->getQueryBuilder();
        $query->delete('appconfig')
            ->where(    $query->expr()->eq('appid',     $query->createNamedParameter('openotp_sign')))
            ->andWhere( $query->expr()->eq('configkey', $query->createNamedParameter('sign_type_mobile')))
        ;
        $query->executeStatement();

        $query = $this->db->getQueryBuilder();
        $query->delete('appconfig')
            ->where(    $query->expr()->eq('appid',     $query->createNamedParameter('openotp_sign')))
            ->andWhere( $query->expr()->eq('configkey', $query->createNamedParameter('sign_scope')))
        ;
        $query->executeStatement();
    }
}
