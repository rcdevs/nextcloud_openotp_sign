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

namespace OCA\OpenOTPSign\Service;

use Exception;
use OCA\OpenOTPSign\AppInfo\Application as RCDevsApp;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstCommon;
use OCA\OpenOTPSign\Utils\CstException;
use OCA\OpenOTPSign\Utils\CstFile;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\Helpers;
use OCA\OpenOTPSign\Utils\LogRCDevs;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\FilesMetadata\IFilesMetadataManager;

class FileService
{
	public Folder $parentFolder;
	public File $file;
	public string $timedName;
	private $extensionSignedFile;
	private $timestamp;

	public function __construct(
		private ConfigurationService $configurationService,
		private IFilesMetadataManager $filesMetadataManager,
		private LogRCDevs $logRCDevs,
		private UserService $user,
		public int $id,
		public bool $toSeal = false,
		public bool $changeExtension = false,
	) {
		try {
			/** @var Node $tmpNode */
			$tmpNode = $user->folder->getById($this->id)[0];

			// Signing a folder is not allowed
			if ($tmpNode->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
				throw new Exception(Constante::exception(CstException::TYPE_NOT_FILE), 1);
			}

			$this->file = $tmpNode;

			// Define extension for signed file (PDF or P7S if source is not a PDF file)
			$this->extensionSignedFile = strcasecmp(pathinfo($this->file->getName(), PATHINFO_EXTENSION), Constante::cst(CstCommon::PDF)) === 0 ? pathinfo($this->file->getName(), PATHINFO_EXTENSION) : Constante::cst(CstCommon::P7S);

			$this->parentFolder = $this->file->getParent();
			$this->timestamp = $user->getTimedLocales();

			// $textualComplement
			$sealComplement = ($this->configurationService->textualComplementSeal() === '' ? RCDevsApp::APP_NAME_SEALED : $this->configurationService->textualComplementSeal());
			$signComplement = ($this->configurationService->textualComplementSign() === '' ? RCDevsApp::APP_NAME_SIGNED : $this->configurationService->textualComplementSign());

			$this->timedName = vsprintf(
				'%s_%s_%s.%s',
				[
					pathinfo($this->file->getName(), PATHINFO_FILENAME), // original filename without extension
					($this->toSeal ?
						$sealComplement :
						$signComplement
					),
					$this->timestamp,
					$this->extensionSignedFile, // original extension
				]
			);
		} catch (\Throwable $th) {
			$this->logRCDevs->error("Issue on file creation {$this->timedName}: {$th->getMessage()}", __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
			throw $th;
		}
	}

	public function create(mixed $temporaryFile, bool $eraseOriginal = false): File
	{
		try {
			// If signed file is not a PDF but P7S, force to "not erase original"
			$eraseOriginal = $eraseOriginal && Helpers::isPdf($this->timedName);

			if ($eraseOriginal) {
				$this->file->putContent($temporaryFile);
				$this->logRCDevs->info(sprintf('File modified [%s]', $this->file->getName()), __FUNCTION__);

				return $this->user->folder->getById($this->id)[0];
			} else {
				$transactionFile = $this->parentFolder->newFile($this->timedName, $temporaryFile);
				$this->logRCDevs->info(sprintf('File created [%s]', $this->timedName), __FUNCTION__);

				$this->id = $transactionFile->getId();
				return $transactionFile;
			}
		} catch (\Throwable $th) {
			$this->logRCDevs->error(vsprintf('%s for %s : [%s]', [Constante::exception(CstException::FILE_CREATION), $this->timedName, $th->getMessage()]), __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__ . DIRECTORY_SEPARATOR . __FILE__ . ':' . __LINE__);
			throw $th;
		}
	}

	public function intel(): array
	{
		$returned = [];
		$message = null;

		try {
			$data = [
				Constante::file(CstFile::CONTENT)	=> $this->file->getContent(),
				Constante::file(CstFile::NAME)		=> $this->file->getName(),
				Constante::file(CstFile::SIZE)		=> $this->file->getSize(),
				Constante::file(CstFile::MTIME)		=> $this->file->getMTime(),
			];

			$returned = [
				Constante::request(CstRequest::CODE)	=> 1,
				Constante::request(CstRequest::DATA)	=> $data,
				Constante::request(CstRequest::ERROR)	=> null,
				Constante::request(CstRequest::MESSAGE)	=> $message,
			];
		} catch (\Throwable $th) {
			$returned = [
				Constante::request(CstRequest::CODE)	=> 0,
				Constante::request(CstRequest::DATA)	=> null,
				Constante::request(CstRequest::ERROR)	=> $th->getCode(),
				Constante::request(CstRequest::MESSAGE)	=> $th->getMessage(),
			];
		}

		return $returned;
	}

	public function getContent(): string
	{
		return $this->file->getContent();
	}

	public function getName(): string
	{
		return $this->file->getName();
	}

	public function getParent(): Folder
	{
		return $this->file->getParent();
	}

	public function getSize(): int|float
	{
		return $this->file->getSize();
	}

	public function getMTime(): int
	{
		return $this->file->getMTime();
	}
}
