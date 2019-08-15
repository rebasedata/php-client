<?php

namespace RebaseData\Service;

use RebaseData\Exception\InvalidArgumentException;

class CopyFilesService
{
    public static function execute($sourceDirectory, $targetDirectory)
    {
        if (!is_dir($sourceDirectory)) {
            throw new InvalidArgumentException('Source directory does not exist: '.$sourceDirectory);
        }

        if (!is_dir($targetDirectory)) {
            throw new InvalidArgumentException('Target directory does not exist: '.$targetDirectory);
        }

        $files = scandir($sourceDirectory);

        foreach ($files as $file) {
            if ($file === '.' or $file === '..') {
                continue;
            }

            copy($sourceDirectory.DIRECTORY_SEPARATOR.$file, $targetDirectory.DIRECTORY_SEPARATOR.$file);
        }
    }
}
