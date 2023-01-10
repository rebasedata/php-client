<?php

namespace RebaseData\Config;

use RebaseData\Exception\InvalidArgumentException;

class Config
{
    private $protocol;
    private $host;
    private $workingDirectory;
    private $apiKey;
    private $cacheEnabled;
    private $cacheDirectory;
    private $useZipIfAvailable;
    private $debugMode;

    public function __construct()
    {
        $this->protocol = 'https';
        $this->host = 'www.rebasedata.com';
        $this->cacheEnabled = false;
        $this->useZipIfAvailable = true;
        $this->debugMode = false;
    }

    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getWorkingDirectory(): string
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
        $this->cacheEnabled = (bool)$cacheEnabled;
    }

    public function getCacheEnabled(): bool
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

    public function getCacheDirectory(): string
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

    public function setUseZipIfAvailable(bool $useZipIfAvailable): void
    {
        $this->useZipIfAvailable = $useZipIfAvailable;
    }

    public function getUseZipIfAvailable(): bool
    {
        return $this->useZipIfAvailable;
    }

    public function setDebugMode(bool $debugMode): void
    {
        $this->debugMode = $debugMode;
    }

    public function getDebugMode(): bool
    {
        return $this->debugMode;
    }
}
