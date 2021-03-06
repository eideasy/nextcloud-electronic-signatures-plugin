<?php

namespace OCA\ElectronicSignatures\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version010300Date20210702111934 extends SimpleMigrationStep {
    /** @var IDBConnection */
    private $dbConnection;

    public function __construct(IDBConnection $dbConnection) {
        $this->dbConnection = $dbConnection;
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

        $table = $schema->getTable('esignature_sessions');
        if (!$table->hasColumn('is_downloaded')) {
            $table->addColumn('is_downloaded', Types::INTEGER, [
                'notnull' => true,
                'length' => 1,
                'default' => 0,
            ]);
        }
        if (!$table->hasColumn('signed_path')) {
            $table->addColumn('signed_path', Types::STRING, [
                'notnull' => false,
                'length' => 4000,
            ]);
        }
        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('esignature_sessions');

        if ($table->hasColumn('used')) {
            $query = $this->dbConnection->getQueryBuilder();
            $query->update('esignature_sessions')
                ->set('is_downloaded', 'used');
            $query->execute();
        }
    }
}
