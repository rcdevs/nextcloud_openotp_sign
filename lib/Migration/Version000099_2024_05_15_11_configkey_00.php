<?php

namespace OCA\OpenOTPSign\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCA\OpenOTPSign\Service\Status;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\IDBConnection;

class Version000099_2024_05_15_11_configkey_00 extends SimpleMigrationStep
{
	const appconfig		 = 'appconfig';
	const appid			 = 'appid';
	const configkey		 = 'configkey';
	const openotp_sign	  = 'openotp_sign';
	const servers_urls	  = 'servers_urls';

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

		$update = $this->connection->getQueryBuilder();
		$update
			->update(self::appconfig)
			->set(self::configkey, $update->createParameter(self::configkey))
			->setParameter(self::configkey, self::servers_urls)
			->where($update->expr()->eq(self::appid, $update->createNamedParameter(self::openotp_sign)))
			->andWhere($update->expr()->eq(self::configkey, $update->createNamedParameter(self::servers_urls)))
			//
		;

		$update->executeStatement();
	}
}
