<?php

namespace AwardWallet\MainBundle\Globals\Utils;

abstract class OutputBufferingUtils
{
    public static function captureOutput(callable $codeBlock): string
    {
        \ob_start();
        $content = '';
        $exceptionWasThrown = true;

        try {
            $codeBlock();
            $exceptionWasThrown = false;
        } finally {
            if ($exceptionWasThrown) {
                \ob_end_clean();
            } else {
                $content = \ob_get_clean();
            }
        }

        return $content;
    }
}
