# rebasedata-php-client

Introduction
------------

This library allows to read and convert various database formats in PHP using the RebaseData API. When processing a database, the database is first sent to the secure RebaseData servers which then return the converted data. See below for a list of examples.

Installation
------------

To install this library, you need [Composer](http://getcomposer.org).

1. To install `rebasedata/php-client` using Composer, run the following command:

    ```bash
    php composer.phar require rebasedata/php-client "1.*"
    ```

2. Then include Composer's autoloader file that helps to autoload the libraries it downloads. To use it, just add the following line to your application:

    ```php
    <?php

    require 'vendor/autoload.php';

    use RebaseData\Converter\Converter;

    $converter = new Converter();
    ```


Examples
--------

List all tables of an ACCDB file.

```php
use RebaseData\InputFile\InputFile;
use RebaseData\Converter\Converter;

$inputFile = new InputFile('/tmp/access.accdb');
$inputFiles = [$inputFile];

$converter = new Converter();
$database = $converter->convertToDatabase($inputFiles);
$tables = $database->getTables();

foreach ($tables as $table) {
    echo "Got table: ".$table->getName()."\n";
}
```

Read the columns of a table called `cars` of an ACCDB file.

```php
use RebaseData\InputFile\InputFile;
use RebaseData\Converter\Converter;

$inputFile = new InputFile('/tmp/access.accdb');
$inputFiles = [$inputFile];

$converter = new Converter();
$database = $converter->convertToDatabase($inputFiles);
$table = $database->getTable('cars');

foreach ($table->getColumns() as $column) {
    echo "Got column: ".$column->getName()."\n";
}
```

Read the rows of a single table called `cars` of an ACCDB file. Since we're using the method `getRowsIterator()` which returns an iterator, the table can also be huge and our memory footprint is still low.

```php
use RebaseData\InputFile\InputFile;
use RebaseData\Converter\Converter;

$inputFile = new InputFile('/tmp/access.accdb');
$inputFiles = [$inputFile];

$converter = new Converter();
$database = $converter->convertToDatabase($inputFiles);
$table = $database->getTable('cars');

foreach ($table->getRowsIterator() as $row) {
    echo "Got row: ";
    foreach ($row as $column => $value) {
        echo "$column = $value ";
    }
    echo "\n";
}
```

If you want to work yourself on the CSV file of a certain table, you can get the CSV file like this:

```php
use RebaseData\InputFile\InputFile;
use RebaseData\Converter\Converter;

$inputFile = new InputFile('/tmp/access.accdb');
$inputFiles = [$inputFile];

$converter = new Converter();
$database = $converter->convertToDatabase($inputFiles);
$table = $database->getTable('cars');

$destinationCsvFilePath = '/tmp/cars.csv';

$table->copyTo($destinationCsvFilePath);

echo "You can find the CSV file in $destinationCsvFilePath\n";
```

Or don't you want to work on the data itself in PHP? Let's assume you want to convert certain input files to a new format and have them stored in a target directory, the following code snippet shows how to do it:

```php
use RebaseData\InputFile\InputFile;
use RebaseData\Converter\Converter;

$inputFile = new InputFile('/tmp/access.accdb');
$inputFiles = [$inputFile];

$targetDirectory = '/tmp/output/';
if (!file_exists($targetDirectory)) {
    mkdir($targetDirectory);
}

$converter = new Converter();
$converter->convertToFormat($inputFiles, 'mysql', $targetDirectory);

echo "You can find the MySQL script file (data.sql) in the following directory: $targetDirectory\n";
```

By the default, the library will use the system's temporary folder as working directory. If you want to change that, you need to adjust the config:

 ```php
 use RebaseData\Config\Config;
 
 $config = new Config();
 $config->setWorkingDirectory('/tmp/rebasedata-working-dir');
  
 $converter = new Converter($config);
 ```

In case you convert the same input files multiple times, you can enable the local cache so that the future conversion
processes are much faster. You can also configure the caching directory. By default, the caching directory is inside of the working directory (see above).

 ```php
 use RebaseData\Config\Config;
 
 $config = new Config();
 $config->setCacheEnabled(true);
 $config->setCacheDirectory('/tmp/cache/');
 
 $converter = new Converter($config);
 ```

For conversions above a certain size, RebaseData requires a Customer Key that you can buy on the website.
You can pass it like this:

 ```php
 use RebaseData\Config\Config;
 use RebaseData\Converter\Converter;
 
 $config = new Config();
 $config->setApiKey('secret value');
 
 $converter = new Converter($config);
 ```
 


Tests
-----

To run tests, run the PHPUnit utility:

```bash
./bin/phpunit
```



License
-------

This code is licensed under the [MIT license](https://opensource.org/licenses/MIT).


Feedback
--------

We love to get feedback from you! Did you discover a bug? Do you need an additional feature? Open an issue on Github and RebaseData will try to resolve your issue as soon as possible! Thanks in advance for your feedback!
