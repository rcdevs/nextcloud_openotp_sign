<?php
namespace OCA\OpenOTPSign\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class SignSession extends Entity implements JsonSerializable {

    private static $timeZone;

    protected $uid;
    protected $path;
    protected $isQualified;
    protected $recipient;
    protected $created;
    protected $session;
    protected $isPending;
    protected $isError;
    protected $message;
    protected $isYumisign;

    public function __construct() {
        $this->addType('id','integer');
        $this->addType('isQualified', 'boolean');
        $this->addType('created', 'datetime');
        $this->addType('isPending', 'boolean');
        $this->addType('isError', 'boolean');
        $this->addType('isYumisign', 'boolean');

        $this->setIsQualified(false);
        $this->setCreated(new \DateTime());
        $this->setIsPending(true);
        $this->setIsError(false);
        $this->setIsYumisign(false);
    }

    public static function __constructStatic() {
        $dateinfo = trim(shell_exec("timedatectl | grep -i zone: 2>/dev/null"));
        $dateinfoarray = explode(' ', $dateinfo);
        self::$timeZone = new \DateTimeZone($dateinfoarray[2]);
    }

    public function jsonSerialize() {
        $this->created->setTimezone(self::$timeZone);

        return [
            'id' => $this->id,
            'path' => $this->path,
            'is_qualified' => $this->isQualified,
            'recipient' => $this->recipient,
            'created' => $this->created->format('Y-m-d H:i:s'),
            'session' => $this->session,
            'message'=> $this->message,
            'is_yumisign' => $this->isYumisign
        ];
    }
}

SignSession::__constructStatic();
