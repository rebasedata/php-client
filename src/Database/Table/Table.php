<?php

namespace RebaseData\Database\Table;

use Traversable;
use RebaseData\Database\Table\Column\Column;
use RebaseData\Service\ReadCsvService;

class Table
{
    private $name;
    private $path;
    private $readCsvService;

    public function __construct($name, $path)
    {
        $this->name = $name;
        $this->path = $path;
        $this->readCsvService = new ReadCsvService();
    }

    public function getName()
    {
        return $this->name;
    }

    public function copyTo($destinationFilePath)
    {
        copy($this->path, $destinationFilePath);
    }

    public function getColumns() : array
    {
        $iterator = $this->readCsvService->execute($this->path);

        $columnNames = $iterator->current();

        $columns = [];
        foreach ($columnNames as $columnName) {
            $columns[] = new Column($columnName);
        }

        return $columns;
    }

    /**
     * Returns an array of all the rows.
     * Should not be used if the file has lot of rows, because everything is loaded in memory.
     * This is not memory-efficient.
     *
     * @return array
     */
    public function getRowsArray() : array
    {
        $iterator = $this->readCsvService->execute($this->path);

        $header = $iterator->current();

        $iterator->next();

        $associativeRows = [];
        while ($iterator->current()) {
            $associativeRows[] = array_combine($header, $iterator->current());

            $iterator->next();
        }

        return $associativeRows;
    }

    /**
     * Allows to iterate through the rows. This allows memory-efficient processing of large tables.
     *
     * @return Traversable
     */
    public function getRowsIterator() : Traversable
    {
        $iterator = $this->readCsvService->execute($this->path);

        $header = $iterator->current();

        $iterator->next();

        while ($iterator->current()) {
            yield array_combine($header, $iterator->current());

            $iterator->next();
        }
    }
}
