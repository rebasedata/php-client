<?php

namespace Tests\RebaseData\Converter;

use PHPUnit\Framework\TestCase;
use RebaseData\Converter\Converter;
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

    public function testConvertAndSaveToDirectoryUsingZipExt()
    {
        $inputFile = new InputFile(dirname(__FILE__).'/../../samples/access.accdb');
        $inputFiles = [$inputFile];

        $targetDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'converter-test-convert-to-format-'.microtime(true);
        mkdir($targetDirectory);

        $converter = new Converter();
        $converter->convertAndSaveToDirectory($inputFiles, 'mysql', $targetDirectory);

        $this->assertFileExists($targetDirectory.DIRECTORY_SEPARATOR.'data.sql');

        DeleteDirectoryService::execute($targetDirectory);
    }

    public function testConvertAndSaveToDirectoryWithoutUsingZipExt()
    {
        $inputFile = new InputFile(dirname(__FILE__).'/../../samples/access.accdb');
        $inputFiles = [$inputFile];

        $targetDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'converter-test-convert-to-format-'.microtime(true);
        mkdir($targetDirectory);

        $converter = new Converter();
        $converter->getConfig()->setUseZipIfAvailable(false); // Do not use ZIP extension
        $converter->convertAndSaveToDirectory($inputFiles, 'mysql', $targetDirectory);

        $this->assertFileExists($targetDirectory.DIRECTORY_SEPARATOR.'data.sql');

        DeleteDirectoryService::execute($targetDirectory);
    }

    public function testConvertAndSaveAsZipFile()
    {
        $inputFile = new InputFile(dirname(__FILE__).'/../../samples/access.accdb');
        $inputFiles = [$inputFile];

        $zipFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'converter-test-convert-to-format-'.microtime(true).'.zip';

        $converter = new Converter();
        $converter->convertAndSaveToZipFile($inputFiles, 'mysql', $zipFile);

        $this->assertFileExists($zipFile);
        $this->assertEquals('PK', substr(file_get_contents($zipFile), 0, 2));

        unlink($zipFile);
    }
}
