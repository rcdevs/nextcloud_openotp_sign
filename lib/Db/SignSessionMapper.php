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
}