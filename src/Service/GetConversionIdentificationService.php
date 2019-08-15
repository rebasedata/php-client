<?php

namespace RebaseData\Service;

class GetConversionIdentificationService
{
    public static function execute(array $inputFiles, $format, array $options = null)
    {
        CheckInputFilesService::execute($inputFiles);

        $identificationInput = '';

        foreach ($inputFiles as $inputFile) {
            if ($identificationInput) {
                $identificationInput .= '|';
            }

            $fileInformation = stat($inputFile->getPath());

            $identificationInput .= 'name='.$inputFile->getName().',';
            $identificationInput .= 'size='.$fileInformation['size'].',';
            $identificationInput .= 'mtime='.$fileInformation['mtime'].',';
            $identificationInput .= 'ino='.$fileInformation['ino'];
        }

        $identificationInput .= '|format='.$format;

        foreach ($options as $key => $value) {
            if ($identificationInput) {
                $identificationInput .= '|';
            }

            $identificationInput .= $key.'='.$value;
        }

        return md5($identificationInput);
    }
}