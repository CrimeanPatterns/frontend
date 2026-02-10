<?php

namespace AwardWallet\MainBundle\Manager\CardImage\RegexpHandler;

class RegexpCompiler implements RegexpCompilerInterface
{
    public const STOP_WORDS = [
        '/^(rewards?|bonus|(?<!choice\s)privileges?)$/',
        '/^affiliate programs?$/',
        '/^airlines?$/',
        '/^awards?$/',
        '/^dollars?$/',
        '/^programs?$/',
        'air',
        'business',
        'card',
        'club card',
        'club',
        'com',
        'discount',
        'frequent flyer',
        'loyalty card',
        'loyalty club',
        'loyalty',
        'miles',
        'plus',
        'plus',
        'rewards club',
        'status',
    ];

    public const MIN_KEYWORD_LENGTH = 3;

    public function compile(string $keyWords)
    {
        $stopwordsRegexp = $this->compileStopwords();

        $words = explode(",", $keyWords);

        foreach ($words as $i => $word) {
            $words[$i] = $word = htmlspecialchars_decode(trim($word));
            $isRegexp = RegexpUtils::isRegExp($word);

            if (
                (
                    !$isRegexp
                    && preg_match($stopwordsRegexp, $word)
                )
                || ('' === $word)
                || (
                    !$isRegexp
                    && (mb_strlen(str_replace(' ', '', $word)) < self::MIN_KEYWORD_LENGTH)
                )
            ) {
                unset($words[$i]);

                continue;
            }

            // RegExp?
            if ($isRegexp) {
                $words[$i] = $word = RegexpUtils::prepareRegExp($word);
            } else {
                $words[$i] = $word = RegexpUtils::prepareKeyword($word);
            }
        }

        $wordsChoice = implode("|", $words);

        if ($words) {
            return "/\b({$wordsChoice})\b/iums";
        }

        return null;
    }

    public function compileStopwords(): string
    {
        static $memoized = null;

        if (isset($memoized)) {
            return $memoized;
        }

        $stopWords = self::STOP_WORDS;

        foreach ($stopWords as $i => $stopWord) {
            if (RegexpUtils::isRegExp($stopWord)) {
                $stopWords[$i] = RegexpUtils::prepareRegExp($stopWord, true);
            } else {
                $stopWords[$i] = RegexpUtils::prepareKeyword($stopWord, true);
            }
        }

        $memoized = "/\b(" . implode("|", $stopWords) . ")\b/iums";

        return $memoized;
    }
}
