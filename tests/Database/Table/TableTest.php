<?php

namespace Tests\RebaseData\Database\Table;

use Iterator;
use PHPUnit\Framework\TestCase;
use RebaseData\Database\Table\Column\Column;
use RebaseData\Database\Table\Table;

class TableTest extends TestCase
{
    public function testGetColumns()
    {
        $path = dirname(__FILE__).'/../../../samples/persons.csv';

        $table = new Table('persons', $path);
        $columns = $table->getColumns();

        $this->assertCount(2, $columns);
        $this->assertInstanceOf(Column::class, $columns[0]);
        $this->assertInstanceOf(Column::class, $columns[1]);
        $this->assertEquals('ID', $columns[0]->getName());
        $this->assertEquals('Name', $columns[1]->getName());
    }

    public function testGetRowsArray()
    {
        $path = dirname(__FILE__).'/../../../samples/persons.csv';

        $table = new Table('persons', $path);

        $this->assertCount(2, $table->getRowsArray());

        $expectedRowsArray = [
            0 => [
                'ID' => '1',
                'Name' => 'Mario',
            ],
            1 => [
                'ID' => '2',
                'Name' => 'Elvis',
            ]
        ];

        $this->assertEquals($expectedRowsArray, $table->getRowsArray());
    }

    public function testGetRowsIterator()
    {
        $path = dirname(__FILE__).'/../../../samples/persons.csv';

        $table = new Table('persons', $path);

        $iterator = $table->getRowsIterator();

        $this->assertInstanceOf(Iterator::class, $iterator);

        $firstRow = null;
        $totalNumberOfRows = 0;
        foreach($iterator as $row) {
            if ($firstRow === null) {
                $firstRow = $row;
            }

            $totalNumberOfRows++;
        }

        $this->assertEquals('Mario', $firstRow['Name']);
        $this->assertEquals(2, $totalNumberOfRows);
    }
}
