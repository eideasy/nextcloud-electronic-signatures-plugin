<?php

namespace OCA\ElectronicSignatures\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000001Date20210415213900 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('esignature_sessions')) {
            $table = $schema->createTable('esignature_sessions');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('token', 'string', [
                'notnull' => true,
                'length' => 30,
            ]);
            $table->addColumn('doc_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 200,
            ]);
            $table->addColumn('path', 'string', [
                'notnull' => true,
                'length' => 4000,
            ]);
            $table->addColumn('used', Types::INTEGER, [
                'notnull' => true,
                'length' => 1,
                'default' => 0,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['doc_id'], 'electronic_signatures_doc_id');
        }
        return $schema;
    }
}
