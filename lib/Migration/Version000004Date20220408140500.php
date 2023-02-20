<?php

  namespace OCA\OpenOTPSign\Migration;

  use Closure;
  use OCP\DB\ISchemaWrapper;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;
  use OCP\IDBConnection;

  class Version000004Date20220408140500 extends SimpleMigrationStep {

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
        $table->dropColumn('is_qualified');

        return $schema;
    }
}
