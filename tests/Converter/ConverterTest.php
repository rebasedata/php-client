<?php

namespace Tests\RebaseData\Converter;

use RebaseData\Converter\Converter;
use PHPUnit\Framework\TestCase;
use RebaseData\Database\Database;
use RebaseData\InputFile\InputFile;
use RebaseData\Service\DeleteDirectoryService;

class ConverterTest extends TestCase
{
    public function testConvertToDatabase()
    {
        $inputFile = new InputFile(dirname(__FILE__).'/../../samples/access.accdb');
        $inputFiles = [$inputFile];

        $converter = new Converter();
        $database = $converter->convertToDatabase($inputFiles);

        $this->assertInstanceOf(Database::class, $database);
        $this->assertCount(2, $database->getTables());
    }

    public function testConvertToFormat()
    {
        $inputFile = new InputFile(dirname(__FILE__).'/../../samples/access.accdb');
        $inputFiles = [$inputFile];

        $targetDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'converter-test-convert-to-format-'.microtime(true);
        mkdir($targetDirectory);

        $converter = new Converter();
        $converter->convertToFormat($inputFiles, 'mysql', $targetDirectory);

        $this->assertFileExists($targetDirectory.DIRECTORY_SEPARATOR.'data.sql');

        DeleteDirectoryService::execute($targetDirectory);
    }
}
