<?php

  namespace OCA\OpenOTPSign\Migration;

  use Closure;
  use OCP\DB\ISchemaWrapper;
  use OCP\Migration\SimpleMigrationStep;
  use OCP\Migration\IOutput;

  class Version000000Date20211108170000 extends SimpleMigrationStep {

    /**
    * @param IOutput $output
    * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
    * @param array $options
    * @return null|ISchemaWrapper
    */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('openotp_sign_sessions')) {
            $table = $schema->createTable('openotp_sign_sessions');
            $table->addColumn('id', 'bigint', [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
            ]);
            $table->addColumn('uid', 'string', [
                'notnull' => true,
                'length' => 64
            ]);
            $table->addColumn('path', 'string', [
                'notnull' => true,
                'length' => 4000,
            ]);
            $table->addColumn('is_qualified', 'boolean', [
                'notnull' => false
            ]);
            $table->addColumn('recipient', 'string', [
                'notnull' => true,
                'length' => 320
            ]);
            $table->addColumn('created', 'datetime', [
                'notnull' => true
            ]);
            $table->addColumn('session', 'string', [
                'notnull' => true,
                'length' => 16,
            ]);
            $table->addColumn('is_pending', 'boolean', [
                'notnull' => false
            ]);
            $table->addColumn('is_error', 'boolean', [
                'notnull' => false
            ]);
            $table->addColumn('message', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['uid'], 'openotp_sign_uid_index');
        }
        return $schema;
    }
}