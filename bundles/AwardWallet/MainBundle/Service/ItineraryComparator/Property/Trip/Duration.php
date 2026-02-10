<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property\Trip;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class Duration extends Property
{
    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }
        $value1 = strtolower($value1);
        $value2 = strtolower($value2);

        // pattern example "2h 32mn", "3hr10m", "5h30mn", "5h"
        $data = [$value1, $value2];
        $error = false;

        foreach ($data as $i => $value) {
            $value = trim($value);

            if (preg_match("/^((\d+)\s*(?:hr?s?|hours?)\s*)?((\d+)\s*(?:mn?|mins?|minutes?))?$/i", $value, $matches)) {
                if (isset($matches[2]) || isset($matches[4])) {
                    $hour = (isset($matches[2])) ? sprintf("%02d", $matches[2]) . 'h' : '00h';
                    $minute = (isset($matches[4])) ? sprintf("%02d", $matches[4]) . 'm' : '00m';
                    $data[$i] = $hour . ' ' . $minute;
                } else {
                    $error = true;
                }
            } elseif (preg_match("/^(\d+)\:(\d+)(h|m)?$/i", $value, $matches)) {
                $hour = sprintf("%02d", $matches[1]) . 'h';
                $minute = sprintf("%02d", $matches[2]) . 'm';
                $data[$i] = $hour . ' ' . $minute;
            } else {
                $error = true;
            }
        }

        if (!$error) {
            return $data[0] == $data[1];
        } else {
            return $value1 == $value2;
        }
    }
}
