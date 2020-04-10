<?php

namespace RebaseData\Service;

use Traversable;
use RebaseData\Exception\InvalidArgumentException;
use RebaseData\Exception\RuntimeException;

/**
 * Class ReadCsvService
 * @package RebaseData\Service
 *
 * See also: https://www.rebasedata.com/csv-specification
 */
class ReadCsvService
{
    public function execute(string $filePath) : Traversable
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('File does not exist: '.$filePath);
        }

        $h = fopen($filePath, 'r');
        if ($h === false) {
            throw new RuntimeException('Cannot open file: '.$filePath);
        }

        $expectedToken = 'row_start';
        $cellValue = null;
        $cells = [];
        $lastCharacter = null;

        while (!feof($h)) {
            $chunk = fread($h, 2048);
            if ($chunk === false) {
                throw new RuntimeException('Cannot read from file');
            }

            if (strlen($chunk) === 0) {
                return;
            }

            foreach (str_split($chunk) as $character) {
                if ($expectedToken === 'row_start' or $expectedToken === 'cell_start') {
                    if ($character !== '"') {
                        throw new RuntimeException('Expected quote, but got: '.$character);
                    }

                    $expectedToken = 'cell_content';
                    $cellValue = '';
                } else if ($expectedToken === 'cell_content') {
                    if ($character === '"') {
                        $expectedToken = 'escaped_quote_or_comma_or_line_end';
                    } else {
                        $cellValue .= $character;
                    }
                } else if ($expectedToken === 'escaped_quote_or_comma_or_line_end') {
                    if ($character === '"') {
                        // if ($lastCharacter = '"')
                        $cellValue .= '"';
                        $expectedToken = 'cell_content';
                    } else if ($character === ',') {
                        $cells[] = $cellValue;
                        $cellValue = null;
                        $expectedToken = 'cell_start';
                    } else if ($character === "\r") { // For \r\n line ending
                        $cells[] = $cellValue;
                        yield $cells;
                        $cells = [];

                        $expectedToken = 'newline';
                    } else if ($character === "\n") { // For \n line ending
                        $cells[] = $cellValue;
                        yield $cells;
                        $cells = [];

                        $expectedToken = 'row_start';
                    } else {
                        throw new RuntimeException('Should not happen: \''.ord($character).'\'');
                    }
                } else if ($expectedToken === "newline") {
                    $expectedToken = 'row_start';
                } else {
                    throw new RuntimeException('Expected token not unknown: '.$expectedToken);
                }

                $lastCharacter = $character;
            }
        }

        if ($expectedToken !== 'escaped_quote_or_comma_or_line_end' and
            $expectedToken !== 'row_start') {
            throw new RuntimeException('CSV ends to early');
        }

        if ($expectedToken === 'escaped_quote_or_comma_or_line_end') {
            yield $cells;
        }
    }
}

