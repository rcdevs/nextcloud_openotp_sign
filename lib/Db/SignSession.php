<?php
namespace OCA\OpenOTPSign\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class SignSession extends Entity implements JsonSerializable {

    private static $timeZone;
    private static $displayTimeZone;

    protected $uid;
    protected $path;
    protected $isAdvanced;
    protected $recipient;
    protected $created;
    protected $session;
    protected $isPending;
    protected $isError;
    protected $message;
    protected $isYumisign;
    protected $expirationDate;

    public function __construct() {
        $this->addType('id','integer');
        $this->addType('isAdvanced', 'boolean');
        $this->addType('created', 'datetime');
        $this->addType('isPending', 'boolean');
        $this->addType('isError', 'boolean');
        $this->addType('isYumisign', 'boolean');
        $this->addType('expirationDate', 'datetime');

        $this->setIsAdvanced(false);
        $this->setCreated(new \DateTime());
        $this->setIsPending(true);
        $this->setIsError(false);
        $this->setIsYumisign(false);
    }

    public static function __constructStatic() {
        if (is_callable('shell_exec') && stripos(ini_get('disable_functions'), 'shell_exec') === false) {
            $timezone = trim(shell_exec('date +%z'));
            self::$displayTimeZone = trim(shell_exec('date +%Z'));
            self::$timeZone = new \DateTimeZone($timezone);
        } else {
            self::$displayTimeZone = date('T');
        }
    }

    public function jsonSerialize() {
        if (self::$timeZone != NULL) {
            $this->created->setTimezone(self::$timeZone);
            $this->expirationDate->setTimezone(self::$timeZone);
        }

        return [
            'id' => $this->id,
            'path' => $this->path,
            'is_advanced' => $this->isAdvanced,
            'recipient' => $this->recipient,
            'created' => $this->created->format('Y-m-d H:i:s ').self::$displayTimeZone,
            'session' => $this->session,
            'message'=> $this->message,
            'is_yumisign' => $this->isYumisign,
            'expiration_date' => $this->expirationDate->format('Y-m-d H:i:s ').self::$displayTimeZone
        ];
    }
}

SignSession::__constructStatic();
