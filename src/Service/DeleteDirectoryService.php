<?php

namespace RebaseData\Service;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RebaseData\Exception\InvalidArgumentException;

class DeleteDirectoryService
{
    public static function execute($directory)
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException('The directory to delete does not exist: '.$directory);
        }

        $iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($directory);
    }
}
