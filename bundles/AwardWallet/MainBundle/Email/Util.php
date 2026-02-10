<?php

namespace AwardWallet\MainBundle\Email;

class Util
{
    public const SAVE_MESSAGE_FAIL = 'fail';
    public const SAVE_MESSAGE_SUCCESS = 'success';
    public const SAVE_MESSAGE_MISSED = 'missed';

    public static function filterHeaders($lines)
    {
        if (is_string($lines)) {
            $lines = preg_split("/(\r?\n|\r)/", trim($lines));
        }
        $currentHeader = null;
        $result = [];

        foreach ($lines as $line) {
            if (preg_match('/^[A-Za-z]/', $line)) { // start of new header
                preg_match('/([^:]+): ?(.*)$/', $line, $matches);

                if (preg_match('/^(Mime\-Version|Content\-Type|Content\-Transfer\-Encoding|Date|Received|Message\-ID|Reply\-To)$/ims', $matches[1])) {
                    $currentHeader = $matches[1];
                    $result[] = $line;
                } else {
                    $currentHeader = null;
                }
            } else { // more lines related to the current header
                if ($currentHeader) { // to prevent notice from empty lines
                    $result[] .= $line;
                }
            }
        }

        return $result;
    }

    public static function clearHeader($header)
    {
        if (preg_match("/<([^>]+)>/ims", $header, $matches)) {
            $header = $matches[1];
        }
        $header = preg_replace("/\([^\)]*\)/ims", "", $header);

        return strtolower(trim($header));
    }

    public static function normalizeTravelerString(string $traveler): string
    {
        return str_replace(' ', '', strtolower($traveler));
    }
}
