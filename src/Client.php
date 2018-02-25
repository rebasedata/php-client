<?php

namespace RebaseData;

use GuzzleHttp\Client as GuzzleClient;
use RebaseData\Exception\ConversionException;
use RebaseData\Exception\InvalidArgumentException;

class Client
{
    private $apiKey;
    private $guzzleClient;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        $this->guzzleClient = new GuzzleClient([
            'base_uri' => 'https://www.rebasedata.com/api/v1/',
        ]);
    }

    public function convertAndReceiveZip(array $inputFiles, $outputFile, array $options = [])
    {
        $this->validateFiles($inputFiles);

        if (strtolower(substr($outputFile, -4)) !== '.zip') {
            throw new InvalidArgumentException('Output file must have .zip extension: '.$outputFile);
        }

        if (empty($options['overwriteOutputFile']) and file_exists($outputFile)) {
            throw new InvalidArgumentException('Output file already exists: '.$outputFile);
        }
        unset($options['overwriteOutputFile']);

        $parts = [];
        foreach ($inputFiles as $inputFile) {
            $parts[] = [
                'name' => 'files[]',
                'filename' => basename($inputFile),
                'contents' => fopen($inputFile, 'r'),
            ];
        }

        $queryString = '';
        if (count($options) > 0) {
            $queryString = '?'.http_build_query($options);
        }

        $response = $this->guzzleClient->request('POST', 'convert'.$queryString, [
            'multipart' => $parts,
            'sink' => $outputFile,
        ]);

        if ($response->getHeader('Content-Type')[0] === 'application/json') {
            $json = json_decode(file_get_contents($outputFile), true);
            unlink($outputFile);

            throw new ConversionException($json['error']);
        }

        return true;
    }

    private function validateFiles(array $files)
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException('Input file does not exist: '.$file);
            }
        }
    }
}
