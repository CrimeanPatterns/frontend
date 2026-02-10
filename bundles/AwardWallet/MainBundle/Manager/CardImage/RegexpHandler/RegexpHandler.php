<?php

namespace AwardWallet\MainBundle\Manager\CardImage\RegexpHandler;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\CardImage\Matcher\MatcherInterface;

class RegexpHandler
{
    public const COMMON_WORDS = [
        'united.{0,5}states',
        'united.{0,5}arab.{0,5}emirates',
        'united.{0,5}kingdom',
    ];

    /**
     * @var MatcherInterface
     */
    private $matcher;

    public function __construct(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    public function matchText(string $text, string $pattern, ?string $stopPattern = null): MatchResult
    {
        if (
            StringUtils::isEmpty($text)
            || StringUtils::isEmpty($text = $this->cleanCommonWords($text))
        ) {
            return new MatchResult(MatchResult::STATUS_NO_MATCH);
        }

        if (
            StringUtils::isNotEmpty($stopPattern)
            && $this->matcher->match($stopPattern, $text)
        ) {
            return new MatchResult(MatchResult::STATUS_STOP_MATCH);
        }

        if ($matches = $this->matcher->match($pattern, $text)) {
            $matchData = [
                'pattern' => $pattern,
                'stopPattern' => $stopPattern,
                'matches' => $matches,
            ];

            return new MatchResult(MatchResult::STATUS_MATCH, $matchData);
        }

        return new MatchResult(MatchResult::STATUS_NO_MATCH);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function cleanCommonWords($text)
    {
        foreach (self::COMMON_WORDS as $regexp) {
            $text = preg_replace("/{$regexp}/ims", '', $text);
        }

        return $text;
    }
}
