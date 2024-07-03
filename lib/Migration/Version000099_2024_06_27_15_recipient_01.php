<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;
use OCP\IUserManager;

class Version000099_2024_06_27_15_recipient_01 extends SimpleMigrationStep
{
	const id					= 'id';
	const openotp_sign_sessions	= 'openotp_sign_sessions';
	const recipient				= 'recipient';
	const username				= 'username';
	const displayName			= 'displayName';

	public function __construct(
		private IDBConnection	$connection,
		private IUserManager	$userManager,
	) {
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
		// Modify Recipient data: from username only, we create a JSON string username+displayname
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::openotp_sign_sessions)) {
			throw new Exception("Table openotp_sign_sessions is missing", 1);;
		}

		$table = $schema->getTable(self::openotp_sign_sessions);

		if ($table->hasColumn(self::recipient)) {
			$query = $this->connection->getQueryBuilder();
			$query->select('*')
				->from(self::openotp_sign_sessions);

			$result = $query->executeQuery();

			$update = $this->connection->getQueryBuilder();
			$update
				->update(self::openotp_sign_sessions)
				->set(self::recipient, $update->createParameter(self::recipient))
				//
			;

			// Update all recipients with username+displayname in a json string
			while ($row = $result->fetch()) {
				// Get displayname from NxC framework

				$recipient = json_encode([
					self::username		=> $row[self::recipient],
					self::displayName	=> $this->userManager->getDisplayName($row[self::recipient])
				]);

				$update
					->setParameter(self::recipient, $recipient)
					->where($update->expr()->eq(self::id, $update->createNamedParameter($row[self::id])))
					//
				;
				$update->executeStatement();
			}
		}
	}
}
