<?php

namespace AwardWallet\MainBundle\Service\Backup;

class Csv
{
    public static function fputcsv($fh, array $fields, $fieldsSeparator = "\t")
    {
        $fields = array_map(function ($value) {
            if ($value === null) {
                return "\\N";
            }

            return addcslashes($value, "\t\n");
        }, $fields);

        return fwrite($fh, implode($fieldsSeparator, $fields) . "\n");
    }
}
