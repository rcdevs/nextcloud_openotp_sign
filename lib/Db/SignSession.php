<?php
namespace OCA\OpenOTPSign\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class SignSession extends Entity implements JsonSerializable {

    protected $uid;
    protected $path;

    /**
     * @var boolean
     */
    protected $isQualified;

    protected $recipient;
    protected $created;
    protected $session;

    public function __construct() {
        $this->addType('id','bigint');
        $this->isQualified = false;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'is_qualified' => $this->isQualified,
            'recipient' => $this->recipient,
            'created' => $screated
        ];
    }
}