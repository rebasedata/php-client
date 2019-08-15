<?php

namespace RebaseData\Config;

use RebaseData\Exception\InvalidArgumentException;

class Config
{
    private $workingDirectory;
    private $apiKey;
    private $cacheEnabled;
    private $cacheDirectory;

    public function __construct()
    {
        $this->cacheEnabled = false;
    }

    public function getWorkingDirectory()
    {
        if ($this->workingDirectory === null) {
            $defaultWorkingDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rebasedata-working-dir';

            if (!file_exists($defaultWorkingDirectory)) {
                mkdir($defaultWorkingDirectory);
            }

            return $defaultWorkingDirectory;
        }

        return $this->workingDirectory;
    }

    public function setWorkingDirectory($workingDirectory)
    {
        if (!is_dir($workingDirectory)) {
            throw new InvalidArgumentException('Working directory does not exist: '.$workingDirectory);
        }

        $this->workingDirectory = $workingDirectory;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setCacheEnabled($cacheEnabled)
    {
        $this->cacheEnabled = (bool) $cacheEnabled;
    }

    public function getCacheEnabled()
    {
        return $this->cacheEnabled;
    }

    public function setCacheDirectory($cacheDirectory)
    {
        if (!is_dir($cacheDirectory)) {
            throw new InvalidArgumentException('Cache directory does not exist: '.$cacheDirectory);
        }

        $this->cacheDirectory = $cacheDirectory;
    }

    public function getCacheDirectory()
    {
        if ($this->cacheDirectory === null) {
            $defaultCacheDirectory = $this->getWorkingDirectory().DIRECTORY_SEPARATOR.'cache';

            if (!file_exists($defaultCacheDirectory)) {
                mkdir($defaultCacheDirectory);
            }

            return $defaultCacheDirectory;
        }

        return $this->cacheDirectory;
    }
}