<?php

namespace RebaseData\Database;

use RebaseData\Exception\InvalidArgumentException;
use RebaseData\Service\DeleteDirectoryService;

class Database
{
    private $directory;
    private $tables;

    public function __construct($directory, array $tables)
    {
        $this->directory = $directory;
        $this->tables = $tables;
    }

    public function __destruct()
    {
        DeleteDirectoryService::execute($this->directory);
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getTable($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Table name must be given!');
        }

        $tables = $this->getTables();

        foreach ($tables as $table) {
            if ($table->getName() === $name) {
                return $table;
            }
        }

        throw new InvalidArgumentException("The database does not have a table called '$name'");
    }
}