<?php
namespace OCA\OpenOTPSign\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class SignSessionMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'openotpsign_sessions', SignSession::class);
    }
}