<?php

/**
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OpenOTPSign\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class SignSession extends Entity implements JsonSerializable
{

	private static $timeZone;
	private static $displayTimeZone;

	protected	$advanced;
	protected	$applicantId;
	protected	$changeStatus;
	protected	$created;
	protected	$expiryDate;
	protected	$fileId;
	protected	$filePath;
	protected	$globalStatus;
	protected	$message;
	protected	$msgDate;
	protected	$mutex;
	protected	$overwrite;
	protected	$recipient;
	protected	$session;
	public		$id;

	public function __construct()
	{
		$this->addType('id',			'integer');
		$this->addType('advanced',		'integer');

		$this->addType('applicantId',	'string');
		$this->addType('changeStatus',	'integer');
		$this->addType('created',		'integer');
		$this->addType('expiryDate',	'integer');
		$this->addType('fileId',		'integer');
		$this->addType('filePath',		'string');
		$this->addType('globalStatus',	'string');
		$this->addType('message',		'string');
		$this->addType('msgDate',		'integer');
		$this->addType('mutex',			'string');
		$this->addType('overwrite',		'integer');
		$this->addType('recipient',		'string');
		$this->addType('session',		'string');

		$this->setCreated(new \DateTime());
	}

	public static function __constructStatic()
	{
		if (is_callable('shell_exec') && stripos(ini_get('disable_functions'), 'shell_exec') === false) {
			$timezone = trim(shell_exec('date +%z'));
			self::$displayTimeZone = trim(shell_exec('date +%Z'));
			self::$timeZone = new \DateTimeZone($timezone);
		} else {
			self::$displayTimeZone = date('T');
		}
	}

	public function isOverwrite(): bool
	{
		try {
			return $this->getOverwrite() === 1;
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function jsonSerialize()
	{
		if (self::$timeZone != NULL) {
			$this->created->setTimezone(self::$timeZone);
			$this->expiryDate->setTimezone(self::$timeZone);
		}

		return [
			'id'				=> $this->id,
			'path'			  => $this->filePath,
			'is_advanced'	   => $this->advanced,
			'recipient'		 => $this->recipient,
			'created'		   => $this->created->format('Y-m-d H:i:s ') . self::$displayTimeZone,
			'session'		   => $this->session,
			'message'		   => $this->message,
			'expiration_date'   => $this->expiryDate->format('Y-m-d H:i:s ') . self::$displayTimeZone
		];
	}
}

SignSession::__constructStatic();
