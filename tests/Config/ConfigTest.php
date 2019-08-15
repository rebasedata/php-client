<?php

namespace RebaseData\Tests\Config;

use RebaseData\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testWorkingDirectory()
    {
        $workingDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'/test-'.microtime(true);
        mkdir($workingDirectory);

        $config = new Config();
        $config->setWorkingDirectory($workingDirectory);

        $this->assertEquals($workingDirectory, $config->getWorkingDirectory());

        rmdir($workingDirectory);
    }

    public function testApiKey()
    {
        $apiKey = 'abcdef';

        $config = new Config();
        $config->setApiKey($apiKey);

        $this->assertEquals($apiKey, $config->getApiKey());
    }
}
