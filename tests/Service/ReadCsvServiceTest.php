<?php

namespace RebaseData\Tests\InputFile;

use RebaseData\Service\ReadCsvService;
use PHPUnit\Framework\TestCase;

class ReadCsvServiceTest extends TestCase
{
    public function testExecute1()
    {
        $filePath = dirname(__DIR__) . '/../samples/persons.csv';

        $service = new ReadCsvService();
        $iterator = $service->execute($filePath);

        $rows = [];
        foreach ($iterator as $row) {
            $rows[] = $row;
        }

        $this->assertEquals([
            ['ID', 'Name'],
            ['1', 'Mario'],
            ['2', 'Elvis'],
        ], $rows);
    }

    public function testExecute2()
    {
        $filePath = dirname(__DIR__) . '/../samples/special.csv';

        $service = new ReadCsvService();
        $iterator = $service->execute($filePath);

        $rows = [];
        foreach ($iterator as $row) {
            $rows[] = $row;
        }

        $this->assertEquals([
            ['value with a quote " and another two quotes ""', 'test'],
            ['val1', "val2\n\nval2"],
        ], $rows);
    }
}
