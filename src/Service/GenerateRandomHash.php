<?php

namespace RebaseData\Service;

class GenerateRandomHash
{
    public static function execute()
    {
        $chars = '0123456789abcdef';

        $result = '';
        for ($i = 0 ; $i < 32 ; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }
}
