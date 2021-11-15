<?php
namespace OCA\OpenOTPSign\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class SignSession extends Entity implements JsonSerializable {

    protected $uid;
    protected $path;
    protected $isQualified;
    protected $recipient;
    protected $created;
    protected $session;
    protected $isPending;
    protected $isError;
    protected $message;

    public function __construct() {
        $this->addType('id','bigint');
        $this->addType('isQualified', 'boolean');
        $this->addType('created', 'datetime');
        $this->addType('isPending', 'boolean');
        $this->addType('isError', 'boolean');

        $this->setIsQualified(false);
        $this->setCreated(new \DateTime());
        $this->setIsPending(true);
        $this->setIsError(false);
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