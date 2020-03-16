<?php

namespace RebaseData\Converter;

use RebaseData\Config\Config;
use RebaseData\Database\Database;
use RebaseData\Database\Table\Table;
use RebaseData\Exception\InvalidArgumentException;
use RebaseData\Exception\LogicException;
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

    public function __construct(Config $config = null)
    {
        if ($config === null) {
            $config = new Config();
        }

        $this->config = $config;
    }

    public function setConfig(Config $config) : void
    {
        $this->config = $config;
    }

    public function getConfig() : Config
    {
        return $this->config;
    }

    public function convertToDatabase(array $inputFiles, array $options = []) : Database
    {
        CheckInputFilesService::execute($inputFiles);

        $randomHash = GenerateRandomHash::execute();

        $databaseDirectory = $this->config->getWorkingDirectory().DIRECTORY_SEPARATOR.'convert-to-database-'.$randomHash;
        mkdir($databaseDirectory);

        $this->convertAndSaveToDirectory($inputFiles, 'csv', $databaseDirectory, $options);

        $csvFiles = GetCsvFilesOfDirectoryService::execute($databaseDirectory);

        $tables = [];
        foreach ($csvFiles as $csvFile) {
            $tables[] = new Table(pathinfo($csvFile, PATHINFO_FILENAME), $databaseDirectory.DIRECTORY_SEPARATOR.$csvFile);
        }

        return new Database($databaseDirectory, $tables);
    }

    public function convertAndSaveToDirectory(array $inputFiles, string $format, string $targetDirectory, array $options = []) : void
    {
        CheckInputFilesService::execute($inputFiles);

        if (empty($format)) {
            throw new InvalidArgumentException('Format must not be empty!');
        }

        if (!is_dir($targetDirectory)) {
            throw new InvalidArgumentException('Target directory '.$targetDirectory.' must exist!');
        }

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

            $this->doConversion($inputFiles, $format, null, $targetDirectory, $options);

            if ($this->config->getCacheEnabled()) {
                mkdir($conversionCacheDirectory);

                CopyFilesService::execute($targetDirectory, $conversionCacheDirectory);

                file_put_contents($conversionCacheDoneMarkerPath, '');
            }
        } catch (Exception $e) {
            throw new RuntimeException('Could not convert', 0, $e);
        }
    }

    public function convertAndSaveToZipFile(array $inputFiles, string $format, string $zipFilePath, array $options = []) : void
    {
        CheckInputFilesService::execute($inputFiles);

        if (empty($format)) {
            throw new InvalidArgumentException('Format must not be empty!');
        }

        if (file_exists($zipFilePath)) {
            throw new InvalidArgumentException('Zip file must not exist yet: '.$zipFilePath);
        }

        $this->doConversion($inputFiles, $format, $zipFilePath, null, $options);
    }

    private function doConversion(array $inputFiles, string $format, ?string $zipFilePath, ?string $targetDirectory, array $options = [])
    {
        CheckInputFilesService::execute($inputFiles);

        if (!($zipFilePath !== null xor $targetDirectory !== null)) {
            throw new InvalidArgumentException('You need to specify the ZIP file path or (exclusive or) the target directory path');
        }

        if ($zipFilePath !== null and file_exists($zipFilePath)) {
            throw new InvalidArgumentException('Zip file must not exist yet: '.$zipFilePath);
        }

        $options['outputFormat'] = $format;

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

        // Establish SSL connection
        if ($this->config->getProtocol() === 'https') {
            $address = 'ssl://'.$this->config->getHost().':443';
        } else {
            $address = 'tcp://'.$this->config->getHost().':80';
        }
        $socket = stream_socket_client($address, $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException('Cannot create socket: '.$errno.' '.$errstr);
        }
        stream_set_timeout($socket, 86400);

        // Calculate the content length
        $contentLength = count($inputFiles) * 8 * 2;
        foreach ($inputFiles as $inputFile) {
            $contentLength += strlen(basename($inputFile->getName()));
            $contentLength += filesize($inputFile->getPath());
        }

        // Build request headers
        if ($zipFilePath !== null) { // We need to deliver a ZIP file
            $acceptHeaderValue = 'application/zip';
        } else { // We need to deliver files in the target directory
            if (extension_loaded('zip') and $this->config->getUseZipIfAvailable()) {
                $acceptHeaderValue = 'application/zip';
            } else {
                $acceptHeaderValue = 'application/rebasedata.v1';
            }
        }

        $requestHeaders = "POST /api/v1/convert$queryString HTTP/1.1\r\n";
        $requestHeaders .= "Host: ".$this->config->getHost()."\r\n";
        $requestHeaders .= "User-Agent: rebasedata/php-client\r\n";
        $requestHeaders .= "Content-Type: application/rebasedata.v1\r\n";
        $requestHeaders .= "Content-Length: $contentLength\r\n";
        $requestHeaders .= "Accept: $acceptHeaderValue\r\n";
        $requestHeaders .= "Connection: Close\r\n";
        $requestHeaders .= "\r\n";

        // Send request headers
        fwrite($socket, $requestHeaders);

        // Send request body
        foreach ($inputFiles as $inputFile) {
            $name = basename($inputFile->getName());

            $nameLength = pack('J', strlen($name));

            fwrite($socket, $nameLength);
            fwrite($socket, $name);

            $contentLength = pack('J', filesize($inputFile->getPath()));

            fwrite ($socket, $contentLength);

            $inputFileHandle = fopen($inputFile->getPath(), 'r');
            while (!feof($inputFileHandle)) {
                $chunk = fread($inputFileHandle, 2048);

                fwrite($socket, $chunk);
            }
            fclose($inputFileHandle);
        }

        // Read response status line
        $line = fgets($socket, 4096);
        preg_match('#^HTTP/\d\.\d (\d+) #', $line, $matches);
        if (!isset($matches[1])) {
            throw new RuntimeException('Could not parse response status line: '.$line);
        }
        $responseCode = (int) $matches[1];
        if ($responseCode !== 200) {
            throw new RuntimeException('Got invalid response code from API: '.$responseCode);
        }

        // Read response headers
        $responseHeaders = [];
        while (!feof($socket)) {
            $line = rtrim(fgets($socket, 4096));

            if ($line === '') {
                break;
            }

            preg_match('/^([a-zA-Z0-9\-]+): (.*)$/', $line, $matches);
            if (!isset($matches[1]) or !isset($matches[2])) {
                throw new RuntimeException('Could not parse response header: '.$line);
            }

            $responseHeaders[$matches[1]] = $matches[2];
        }

        // Check if we got an error back
        if (isset($responseHeaders['Content-Type']) and
            $responseHeaders['Content-Type'] === 'application/json') {
            $responseData = fgets($socket);

            $json = json_decode($responseData, true);
            throw new RuntimeException('Got error from API: '.$json['error']);
        }

        // Handle response body
        if ($zipFilePath !== null) {
            // Read connection and write data to ZIP file

            $zipFileHandle = fopen($zipFilePath, 'w');
            if ($zipFileHandle === false) {
                throw new RuntimeException('Cannot open ZIP file: ' . $zipFilePath);
            }

            while (!feof($socket)) {
                $chunk = fgets($socket, 2048);
                fwrite($zipFileHandle, $chunk);
            }

            fclose($socket);
            fclose($zipFileHandle);
        } else {
            // We need to deliver the files to the target directory

            if ($responseHeaders['Content-Type'] === 'application/zip') {
                // Receive zip file and extract it to target directory

                if (!extension_loaded('zip')) {
                    throw new LogicException('Should not happen');
                }

                $workingDirectory = $this->config->getWorkingDirectory();
                $randomHash = GenerateRandomHash::execute();

                $temporaryZipFilePath = $workingDirectory.DIRECTORY_SEPARATOR.'convert-to-format-zip-'.$randomHash;

                $temporaryZipFileHandle = fopen($temporaryZipFilePath, 'w+');
                while (!feof($socket)) {
                    $chunk = fread($socket, 2048);

                    fwrite($temporaryZipFileHandle, $chunk);
                }
                fclose($temporaryZipFileHandle);
                fclose($socket);

                $zipArchive = new ZipArchive();
                $zipArchive->open($temporaryZipFilePath);
                $zipArchive->extractTo($targetDirectory);
                $zipArchive->close();

                unlink($temporaryZipFilePath);

            } else if ($responseHeaders['Content-Type'] === 'application/rebasedata.v1') {
                // We need to deliver the files to the target directory. Since we don't have ext-zip, we
                // receive a RebaseData-encoded binary response instead of a ZIP file.

                while (!feof($socket)) {
                    $nameLength = fread($socket, 8);
                    $nameLength = unpack('J', $nameLength);
                    $nameLength = $nameLength[1];

                    $name = fread($socket, $nameLength);

                    if (strstr($name, '/') or strstr($name, '\\')) {
                        throw new InvalidArgumentException('Not allowed for security reasons');
                    }

                    $contentLength = fread($socket, 8);
                    $contentLength = unpack('J', $contentLength);
                    $contentLength = $contentLength[1];

                    $outputFilePath = $targetDirectory.DIRECTORY_SEPARATOR.$name;

                    $contentDone = 0;
                    $outputFileHandle = fopen($outputFilePath, 'w+');
                    while ($contentDone < $contentLength) {
                        $toRead = $contentLength - $contentDone;
                        if ($toRead > 2048) {
                            $toRead = 2048;
                        }

                        $chunk = fread($socket, $toRead);

                        fwrite($outputFileHandle, $chunk);

                        $contentDone += strlen($chunk);
                    }
                    fclose($outputFileHandle);
                }

                fclose($socket);
            } else {
                throw new RuntimeException('Got response with invalid Content-Type header: '.$responseHeaders['Content-Type']);
            }
        }
    }

    /**
     * @deprecated
     */
    public function convertToFormat(array $inputFiles, string $format, string $targetDirectory, array $options = []) : void
    {
        $this->convertAndSaveToDirectory($inputFiles, $format, $targetDirectory, $options);
    }

    /**
     * @deprecated
     */
    public function convertToFormatAndSaveAsZipFile(array $inputFiles, string $format, string $zipFilePath, array $options = []) : void
    {
        $this->doConversion($inputFiles, $format, $zipFilePath, null, $options);
    }
}
