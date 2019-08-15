<?php

namespace RebaseData\Converter;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use RebaseData\Config\Config;
use RebaseData\Database\Database;
use RebaseData\Database\Table\Table;
use RebaseData\Exception\InvalidArgumentException;
use RebaseData\Exception\RuntimeException;
use RebaseData\Service\CheckInputFilesService;
use RebaseData\Service\CopyFilesService;
use RebaseData\Service\DeleteDirectoryService;
use RebaseData\Service\GenerateRandomHash;
use RebaseData\Service\GetCsvFilesOfDirectoryService;
use RebaseData\Service\GetConversionIdentificationService;
use ZipArchive;

class Converter
{
    private $config;

    public function __construct()
    {
        $this->config = new Config();
    }

    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function convertToDatabase(array $inputFiles, array $options = [])
    {
        CheckInputFilesService::execute($inputFiles);

        $randomHash = GenerateRandomHash::execute();

        $databaseDirectory = $this->config->getWorkingDirectory().DIRECTORY_SEPARATOR.'convert-to-database-'.$randomHash;
        mkdir($databaseDirectory);

        $converter = new Converter();
        $converter->setConfig($this->config);
        $converter->convertToFormat($inputFiles, 'csv', $databaseDirectory, $options);

        $csvFiles = GetCsvFilesOfDirectoryService::execute($databaseDirectory);

        $tables = [];
        foreach ($csvFiles as $csvFile) {
            $tables[] = new Table(pathinfo($csvFile, PATHINFO_FILENAME), $databaseDirectory.DIRECTORY_SEPARATOR.$csvFile);
        }

        return new Database($databaseDirectory, $tables);
    }

    public function convertToFormat(array $inputFiles, $format, $targetDirectory, array $options = [])
    {
        CheckInputFilesService::execute($inputFiles);

        if (empty($format)) {
            throw new InvalidArgumentException('Format must not be empty!');
        }

        if (!is_dir($targetDirectory)) {
            throw new InvalidArgumentException('Target directory '.$targetDirectory.' must exist!');
        }

        $options['outputFormat'] = $format;

        try {
            $conversionCacheDirectory = null;
            $conversionCacheDoneMarkerPath = null;
            if ($this->config->getCacheEnabled()) {
                $cacheDirectory = $this->config->getCacheDirectory();
                $conversionIdentification = GetConversionIdentificationService::execute($inputFiles, $format, $options);

                $conversionCacheDirectory = $cacheDirectory . DIRECTORY_SEPARATOR . 'convert-to-format-' . $conversionIdentification;
                $conversionCacheDoneMarkerPath = $conversionCacheDirectory . DIRECTORY_SEPARATOR . 'done.marker';

                if (is_dir($conversionCacheDirectory)) {
                    if (file_exists($conversionCacheDoneMarkerPath)) {
                        CopyFilesService::execute($conversionCacheDirectory, $targetDirectory);

                        return;
                    }

                    DeleteDirectoryService::execute($conversionCacheDirectory);
                }
            }

            $workingDirectory = $this->config->getWorkingDirectory();

            $randomHash = GenerateRandomHash::execute();

            $temporaryZipFilePath = $workingDirectory.DIRECTORY_SEPARATOR.'convert-to-format-zip-'.$randomHash;

            $this->convertToFormatAndReceiveZipFile($inputFiles, $temporaryZipFilePath, $options);

            $zipArchive = new ZipArchive();
            $zipArchive->open($temporaryZipFilePath);
            $zipArchive->extractTo($targetDirectory);
            $zipArchive->close();

            unlink($temporaryZipFilePath);

            if ($this->config->getCacheEnabled()) {
                mkdir($conversionCacheDirectory);

                CopyFilesService::execute($targetDirectory, $conversionCacheDirectory);

                file_put_contents($conversionCacheDoneMarkerPath, '');
            }
        } catch (Exception $e) {
            throw new RuntimeException('Could not convert', 0, $e);
        }
    }

    private function convertToFormatAndReceiveZipFile(array $inputFiles, $zipFile, array $options = [])
    {
        CheckInputFilesService::execute($inputFiles);

        if (file_exists($zipFile)) {
            throw new InvalidArgumentException('Zip file must not exist yet: '.$zipFile);
        }

        if ($this->config->getApiKey()) {
            $options['customerKey'] = $this->config->getApiKey();
        }

        $parts = [];
        foreach ($inputFiles as $inputFile) {
            $parts[] = [
                'name' => 'files[]',
                'filename' => basename($inputFile->getName()),
                'contents' => fopen($inputFile->getPath(), 'r'),
            ];
        }

        $queryString = '';
        if (count($options) > 0) {
            $queryString = '?'.http_build_query($options);
        }

        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://www.rebasedata.com/api/v1/',
        ]);

        $response = $guzzleClient->request('POST', 'convert'.$queryString, [
            'multipart' => $parts,
            'sink' => $zipFile,
        ]);

        if ($response->getHeader('Content-Type')[0] === 'application/json') {
            $json = json_decode(file_get_contents($zipFile), true);
            unlink($zipFile);

            throw new RuntimeException($json['error']);
        }

        return $zipFile;
    }
}
