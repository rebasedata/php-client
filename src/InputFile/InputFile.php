<?php

namespace RebaseData\InputFile;

use RebaseData\Exception\InvalidArgumentException;

class InputFile
{
    private $path;
    private $name;

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException('Path must exist: '.$path);
        }

        $this->path = $path;
        $this->name = basename($path);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setName($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Name must not be empty!');
        }

        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
