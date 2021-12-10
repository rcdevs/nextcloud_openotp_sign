<?php
namespace OCA\OpenOTPSign\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class SignSessionMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'openotp_sign_sessions', SignSession::class);
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

    public function findFailedByUid(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where('is_pending=false')
           ->andWhere('is_error=true')
           ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
           ->orderBy('created', 'desc');

        return $this->findEntities($qb);
    }
}