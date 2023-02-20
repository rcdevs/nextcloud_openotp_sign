<?php

  namespace OCA\OpenOTPSign\Migration;

  use Closure;
  use OCP\DB\ISchemaWrapper;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

  class Version000001Date20211210145000 extends SimpleMigrationStep {

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
        $table->addColumn('is_yumisign', 'boolean', [
            'notnull' => false
        ]);

        $table->addIndex(['session'], 'openotp_sign_session_index');

        return $schema;
    }
}