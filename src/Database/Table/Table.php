<?php

namespace RebaseData\Database\Table;

use League\Csv\Reader;
use RebaseData\Database\Table\Column\Column;

class Table
{
    private $name;
    private $path;

    public function __construct($name, $path)
    {
        $this->name = $name;
        $this->path = $path;
    }

    public function getName()
    {
        return $this->name;
    }

    public function copyTo($destinationFilePath)
    {
        copy($this->path, $destinationFilePath);
    }

    public function getColumns()
    {
        $reader = Reader::createFromPath($this->path, 'r');
        $columnNames = $reader->fetchOne();

        $columns = [];
        foreach ($columnNames as $columnName) {
            $columns[] = new Column($columnName);
        }

        return $columns;
    }

    /**
     * Returns an array of all the rows.
     * Should not be used if the file has lot of rows, because everything is loaded in memory.
     *
     * @return array
     */
    public function getRowsArray()
    {
        $reader = Reader::createFromPath($this->path, 'r');

        $header = $reader->fetchOne();

        $rows = $reader->setOffset(1)->fetchAll();

        $associativeRows = [];
        foreach ($rows as $row) {
            $associativeRows[] = array_combine($header, $row);
        }

        return $associativeRows;
    }

    /**
     * Allows to iterate through the rows. This allows memory-efficient processing of large tables.
     *
     * @return Traversable
     */
    public function getRowsIterator()
    {
        $reader = Reader::createFromPath($this->path, 'r');

        $header = $reader->fetchOne();

        $iterator = $reader->setOffset(1)->fetchAssoc($header);

        return $iterator;
    }
}
