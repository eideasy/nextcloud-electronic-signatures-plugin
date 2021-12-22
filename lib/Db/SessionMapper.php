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

    /**
     * @param string $userId
     * @param string $path
     * @return array|\OCP\AppFramework\Db\Entity[]
     * @throws \OCP\DB\Exception
     */
    public function findByPath(
        string $userId,
        string $signedPath
    ): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
            )->andWhere(
                $qb->expr()->eq('signed_path', $qb->createNamedParameter($signedPath))
            );

        return $this->findEntities($qb);
    }
}
