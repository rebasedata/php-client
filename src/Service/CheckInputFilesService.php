<?php

namespace RebaseData\Service;

use RebaseData\Exception\InvalidArgumentException;
use RebaseData\InputFile\InputFile;

class CheckInputFilesService
{
    public static function execute(array $inputFiles)
    {
        foreach ($inputFiles as $inputFile) {
            if (!$inputFile instanceof InputFile) {
                throw new InvalidArgumentException('The input files array must only have InputFile instances!');
            }
        }
    }
}
