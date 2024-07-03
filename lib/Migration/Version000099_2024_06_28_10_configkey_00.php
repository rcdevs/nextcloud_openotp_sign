<?php

namespace OCA\OpenOTPSign\Migration;

use Aws\DynamoDb\SetValue;
use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCA\OpenOTPSign\Service\Status;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_06_28_10_configkey_00 extends SimpleMigrationStep
{
	const appconfig		= 'appconfig';
	const appid			= 'appid';
	const configkey		= 'configkey';
	const configvalue	= 'configvalue';
	const openotp_sign	= 'openotp_sign';
	const overwrite		= 'overwrite';
	const signed_file	= 'signed_file';

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
		// Add existing data inside TMP column
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::appconfig)) {
			throw new Exception("Table appconfig is missing", 1);;
		}

		// Get current setting value
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from(self::appconfig)
			->where(	$query->expr()->eq(self::appid,		$query->createNamedParameter(self::openotp_sign)))
			->andWhere(	$query->expr()->eq(self::configkey,	$query->createNamedParameter(self::signed_file)))
			//
		;

		$result = $query->executeQuery();

		while ($row = $result->fetch()) {
			$insert = $this->connection->getQueryBuilder();
			$insert
				->insert(self::appconfig)
				->setValue(self::appid, $insert->createParameter(self::appid))
				->setValue(self::configkey, $insert->createParameter(self::configkey))
				->setValue(self::configvalue, $insert->createParameter(self::configvalue))
				->setParameter(self::appid, self::openotp_sign)
				->setParameter(self::configkey, self::overwrite)
				->setParameter(self::configvalue, (
					strcasecmp($row[self::configvalue], 'copy') === 0
					? 0
					: 1
				))
				//
			;
			$insert->executeStatement();
		};

		// Delete old setting
		$delete = $this->connection->getQueryBuilder();
		$delete->delete(self::appconfig)
			->where(	$delete->expr()->eq(self::appid,		$delete->createNamedParameter(self::openotp_sign)))
			->andWhere(	$delete->expr()->eq(self::configkey,	$delete->createNamedParameter(self::signed_file)))
			//
		;
		$delete->executeStatement();
	}
}
