<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Provider;
use Psr\Log\LoggerInterface;

class CreditCardMatcher
{
    public const DETECT_STOP = [
        Provider::CITI_ID => ['|'],
    ];
    /** @var LoggerInterface */
    private $logger;
    private CreditCardPatternsSource $patternsSource;

    public function __construct(LoggerInterface $logger, CreditCardPatternsSource $patternsSource)
    {
        $this->logger = $logger;
        $this->patternsSource = $patternsSource;
    }

    /** return CreditCardID
     * returned value null means unknown card.
     */
    public function identify(string $name, int $providerId): ?int
    {
        $patterns = $this->patternsSource->getPatterns();

        if (empty($patterns[$providerId])) {
            return null;
        }

        if (isset(self::DETECT_STOP[$providerId])) {
            foreach (self::DETECT_STOP[$providerId] as $stopString) {
                if (strpos($name, $stopString) !== false) {
                    return null;
                }
            }
        }

        $result = null;
        $matchedPatterns = [];
        $cleanName = self::cleanSpecialSymbols($name);

        foreach ($patterns[$providerId] as $cardId => $card) {
            foreach ($card["Patterns"] as $pattern) {
                $isPreg = substr($pattern, 0, 1) === '#';
                $match = $isPreg
                    ? preg_match($pattern, $name) === 1
                    : stripos($cleanName, $pattern) !== false;

                if ($match && $result === null) {
                    $result = $cardId;
                }

                if ($match) {
                    $matchedPatterns[] = ["CreditCardID" => $cardId, "Pattern" => $pattern, 'MatchingOrder' => $card['MatchingOrder']];

                    break;
                }
            }
        }

        $this->reportMultipleMatches($matchedPatterns, $name, $result);

        return $result;
    }

    public static function cleanSpecialSymbols(string $cardName): string
    {
        $symbols = [
            '®', '&reg;', '&#174;', '&#x00AE;',
            '©', '&copy;', '&#169;', '&#xA9;',
            'Ⓒ', '&#9400;', '&#x24B8;',
            '™', '&trade;', '&#8482;', '&#x2122;',
            '℠', '&#8480;',
            '✓', '&#10003;', '&#x2713;',
            '✔', '&#10004;', '&#x2714;',
            '"', '&quot;', '&#34;', '&#x22;',
            "'", '&apos;', '&#39;', '&#x27;',
            '`', '&#96;', '&#x60;',
            '＂', '&#65282;', '&#xFF02;',
            '＇', '&#65287;', '&#xFF07;',
        ];

        $cleanName = strip_tags($cardName);
        $cleanName = str_replace($symbols, ' ', $cleanName);

        return preg_replace('/\s+/', ' ', $cleanName);
    }

    private function reportMultipleMatches(array $matchedPatterns, string $name, ?int $result): void
    {
        if (count($matchedPatterns) < 2) {
            return;
        }

        $byMatchingOrder = [];

        foreach ($matchedPatterns as $matchedPattern) {
            if (!isset($byMatchingOrder[$matchedPattern['MatchingOrder']])) {
                $byMatchingOrder[$matchedPattern['MatchingOrder']] = [];
            }

            $byMatchingOrder[$matchedPattern['MatchingOrder']][] = $matchedPattern;
        }

        $byMatchingOrder = array_filter($byMatchingOrder, fn (array $matchedPatterns) => count($matchedPatterns) > 1);

        foreach ($byMatchingOrder as $matchingOrder => $matchedPatterns) {
            $this->logger->info("cc_multiple_matches", [
                "Name" => $name,
                "Matches" => $matchedPatterns,
                "MatchedCreditCards" => '[' . implode('][', array_column($matchedPatterns, "CreditCardID")) . ']',
                "MatchedPatterns" => '[' . implode('][', array_map(fn (array $pattern) => $pattern['CreditCardID'] . '-' . $pattern['Pattern'], $matchedPatterns)) . ']',
                "CreditCardID" => $result,
            ]);

            foreach ($matchedPatterns as $matchedPattern) {
                $this->logger->info("cc_multiple_match", array_merge(["Name" => $name], $matchedPattern));
            }
        }
    }
}
