<?php

namespace OCA\ElectronicSignatures\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version020000Date20220425141442 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('remote_signing_queues')) {
            $table = $schema->createTable('remote_signing_queues');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('signing_queue_id', 'string', [
                'notnull' => true,
                'length' => 30,
            ]);
            $table->addColumn('signing_queue_secret', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('original_file_path', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('is_downloaded', Types::BOOLEAN, [
                'default' => false,
                'notnull' => false,
            ]);

            $table->setPrimaryKey(['id']);
        }
        return $schema;
    }
}
