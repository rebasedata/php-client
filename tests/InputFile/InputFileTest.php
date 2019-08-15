<?php

namespace RebaseData\Tests\InputFile;

use RebaseData\InputFile\InputFile;
use PHPUnit\Framework\TestCase;

class InputFileTest extends TestCase
{
    public function test()
    {
        $path = dirname(__DIR__) . '/../samples/access.accdb';

        $inputFile = new InputFile($path);

        $this->assertEquals($path, $inputFile->getPath());
        $this->assertEquals('access.accdb', $inputFile->getName());
    }
}
