<?php

namespace AwardWallet\MainBundle\Manager\CardImage\RegexpHandler;

class RegexpUtils
{
    public static function isRegExp(string $keyword): bool
    {
        return
            substr($keyword, 0, 1) == '/'
            && substr($keyword, -1) == '/';
    }

    public static function prepareKeyword(string $keyword, bool $isStopword = false): string
    {
        $keyword = preg_quote($keyword);
        $keyword = str_replace(" ", '\s*', $keyword);
        $keyword = str_replace("/", '\\/', $keyword);

        if ($isStopword) {
            $keyword = "^{$keyword}$";
        }

        return $keyword;
    }

    public static function prepareRegExp(string $regexp, bool $isStopword = false): string
    {
        $regexp = substr_replace($regexp, "", -1);
        $regexp = substr_replace($regexp, "", 0, 1);

        if ($isStopword) {
            $regexp = "^{$regexp}$";
        }

        return "($regexp)";
    }
}
