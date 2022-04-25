<?php
namespace OCA\ElectronicSignatures\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class RemoteSigningQueueMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'remote_signing_queues', RemoteSigningQueue::class);
    }

    public function findByQueueId(string $queueId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('signing_queue_id', $qb->createNamedParameter($queueId))
            );

        return $this->findEntity($qb);
    }
}
