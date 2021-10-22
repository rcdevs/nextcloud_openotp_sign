<?php

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
