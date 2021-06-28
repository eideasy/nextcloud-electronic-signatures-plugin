<?php
namespace OCA\ElectronicSignatures\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class SessionMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'esignature_sessions', Session::class);
    }

    public function findByDocId(string $docId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('doc_id', $qb->createNamedParameter($docId))
            );

        return $this->findEntity($qb);
    }

    public function findByToken(string $token) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('token', $qb->createNamedParameter($token))
            );

        return $this->findEntity($qb);
    }
}
