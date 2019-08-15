<?php

namespace RebaseData\Tests\Database;

use RebaseData\Database\Database;
use RebaseData\Database\Table\Table;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function test()
    {
        $databaseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'database-test-'.microtime(true);
        mkdir($databaseDirectory);

        $table1 = new Table('persons', '/tmp/persons.csv');
        $table2 = new Table('cars', '/tmp/cars.csv');

        $tables = [$table1, $table2];

        $database = new Database($databaseDirectory, $tables);

        $tables = $database->getTables();
        $personsTable = $database->getTable('persons');
        $carsTable = $database->getTable('cars');

        $this->assertCount(2, $tables);
        $this->assertInstanceOf(Table::class, $personsTable);
        $this->assertInstanceOf(Table::class, $carsTable);
    }
}
