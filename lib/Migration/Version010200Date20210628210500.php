<?php

namespace OCA\ElectronicSignatures\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version010200Date20210628210500 extends SimpleMigrationStep {
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
        $table->addColumn('is_downloaded', Types::INTEGER, [
            'notnull' => true,
            'length' => 1,
            'default' => 0,
        ]);
        $table->addColumn('signed_path', 'string', [
            'notnull' => false,
            'length' => 4000,
        ]);
        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
        $query = $this->dbConnection->getQueryBuilder();
        $query->update('esignature_sessions')
            ->set('is_downloaded', 'used');
        $query->executeStatement();
    }
}
