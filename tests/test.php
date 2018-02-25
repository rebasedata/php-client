<?php

declare(strict_types = 1);
error_reporting(E_ALL | E_STRICT);

require_once dirname(__DIR__).'/vendor/autoload.php';

use RebaseData\Client;
use RebaseData\Exception\ConversionException;

$inputFiles = [dirname(__DIR__).'/samples/access.accdb'];
$outputFile = '/tmp/out.zip';
$options = ['overwriteOutputFile' => true, 'outputFormat' => 'xlsx'];

try {
    echo "Executing conversion, this might take some time..\n";

    $client = new Client('apiKey');
    $client->convertAndReceiveZip($inputFiles, $outputFile, $options);

    echo "Conversion successful!\n";
    echo "You can find the ZIP archive containing the XLSX files in $outputFile\n";
} catch (ConversionException $e) {
    echo "Conversion failed: ".$e->getMessage()."\n";
}
