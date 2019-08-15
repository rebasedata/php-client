<?php

namespace RebaseData\Service;

use RebaseData\Exception\InvalidArgumentException;

class GetCsvFilesOfDirectoryService
{
    public static function execute($dir)
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException($dir.' is not a directory!');
        }

        $files = [];

        $dh = opendir($dir);
        if ($dh === false) {
            throw new InvalidArgumentException('Could not open directory');
        }

        while (($file = readdir($dh)) !== false) {
            if ($file == '.' or $file == '..' or substr($file, -4) !== '.csv') {
                continue;
            }

            $files[] = $file;
        }
        closedir($dh);

        return $files;
    }
}
