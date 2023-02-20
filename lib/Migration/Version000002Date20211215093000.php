<?php

  namespace OCA\OpenOTPSign\Migration;

  use Closure;
  use OCP\DB\ISchemaWrapper;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;
  use OCP\IDBConnection;

  class Version000002Date20211215093000 extends SimpleMigrationStep {

    private $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('openotp_sign_sessions');
        $table->addColumn('expiration_date', 'datetime', [
            'notnull' => false
        ]);

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        $query = $this->db->getQueryBuilder();
        $query->update('openotp_sign_sessions')
                ->set('expiration_date', 'created');
        $query->execute();
    }
}