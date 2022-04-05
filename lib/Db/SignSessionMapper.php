<?php
namespace OCA\OpenOTPSign\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class SignSessionMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'openotp_sign_sessions', SignSession::class);
    }

    public function findAllPending() {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where('is_pending=true');

        return $this->findEntities($qb);
    }

    public function findPendingsByUid(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where('is_pending=true')
           ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
           ->orderBy('created', 'desc');

        return $this->findEntities($qb);
    }

    public function findCompletedByUid(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where('is_pending=false')
           ->andWhere('is_error=false')
           ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
           ->orderBy('created', 'desc');

        return $this->findEntities($qb);
    }

    public function countFailedByUid(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select($qb->createFunction('COUNT(*)'))
           ->from($this->getTableName())
           ->where('is_pending=false')
           ->andWhere('is_error=true')
           ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));

        $result = $qb->executeQuery();
        $count = $result->fetchOne();
        $result->closeCursor();

        return $count;
    }

    public function findFailedByUid(string $uid, int $page = 0, int $nbItems = 20) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where('is_pending=false')
           ->andWhere('is_error=true')
           ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
           ->orderBy('created', 'desc');

        $qb->setFirstResult($page * $nbItems);
        $qb->setMaxResults($nbItems);

        return $this->findEntities($qb);
    }

    public function findBySession(string $session) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where('is_pending=true')
           ->andWhere($qb->expr()->eq('session', $qb->createNamedParameter($session)));

        return $this->findEntity($qb);
    }
}