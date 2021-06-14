<?php

namespace OCA\ElectronicSignatures\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version010100Date20210610100900 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('esignature_sessions');
        $table->addColumn('is_hash_based', 'integer', [
            'length' => 1,
            'notnull' => true,
            'default' => 0,
        ]);
        $table->addColumn('container_type', 'string', [
            'length' => 10,
            'notnull' => true,
            'default' => 'asice',
        ]);
        return $schema;
    }
}
