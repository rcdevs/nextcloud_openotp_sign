<?php
/**
 *
 * @copyright Copyright (c) 2021, RCDevs (info@rcdevs.com)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OpenOTPSign\Commands;

use OCP\Files\NotFoundException;

trait GetsFile {
    private function getFile(string $path, $userId): array {
        $userFolder = $this->storage->getUserFolder($userId);

        try {
            $file = $userFolder->get($path);

            if ($file instanceof \OCP\Files\File) {
                return [$file->getContent(), $file->getName(), $file->getSize(), $file->GetMTime()];
            } else {
                throw new NotFoundException('Can not read from folder');
            }
        } catch (NotFoundException $e) {
            throw new NotFoundException('File does not exist');
        }
    }

    private function saveContainer($userId, string $contents, string $containerPath): void
    {
        $userFolder = $this->storage->getUserFolder($userId);

        $userFolder->touch($containerPath);
        $userFolder->newFile($containerPath, $contents);
    }


    private function humanFileSize($size,$unit="") {
        if( (!$unit && $size >= 1<<30) || $unit == "GB")
          return number_format($size/(1<<30),2)." GB";
        if( (!$unit && $size >= 1<<20) || $unit == "MB")
          return number_format($size/(1<<20),2)." MB";
        if( (!$unit && $size >= 1<<10) || $unit == "KB")
          return number_format($size/(1<<10),2)." KB";
        return number_format($size)." bytes";
      }
}
