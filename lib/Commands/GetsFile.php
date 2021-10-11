<?php

namespace OCA\OpenOTPSign\Commands;

use OCP\Files\NotFoundException;

trait GetsFile {
    private function getFile(string $path, $userId): array {
        $userFolder = $this->storage->getUserFolder($userId);

        try {
            $file = $userFolder->get($path);

            if ($file instanceof \OCP\Files\File) {
                return [$file->getMimeType(), $file->getContent(), $file->getName()];
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
}
