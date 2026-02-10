<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use Symfony\Component\Routing\RouterInterface;

class DoublePatternsValidator
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * will show conflicts for currently edited credit card.
     *
     * @param array $patterns - [<ProviderID1> => [<CreditCardID1> => ['Patterns' => ['pattern1', '#pattern2#ims']], ...]
     * @param int $creditCardId - currently edited credit card
     * @return string[] - array of errors, empty array if no errors
     */
    public function validate(array $patterns, int $creditCardId): array
    {
        $matches = [];

        foreach ($patterns as $providerId => $cards) {
            $stringPatterns = [];

            foreach ($cards as $cardId => $card) {
                foreach ($card["Patterns"] as $pattern) {
                    if (substr($pattern, 0, 1) !== '#') {
                        $stringPatterns[] = ["Pattern" => $pattern, "CreditCardID" => $cardId, "ProviderID" => $providerId];
                    }
                }
            }

            usort($stringPatterns, function ($a, $b) {
                $result = strlen($a["Pattern"]) <=> strlen($b["Pattern"]);

                if ($result !== 0) {
                    return $result;
                }

                return strcasecmp($a['Pattern'], $b['Pattern']);
            });

            foreach ($stringPatterns as $index => $pattern) {
                for ($matchIndex = $index + 1; $matchIndex < count($stringPatterns); $matchIndex++) {
                    if ($pattern['CreditCardID'] != $creditCardId && $stringPatterns[$matchIndex]['CreditCardID'] != $creditCardId) {
                        continue;
                    }

                    if (stripos($stringPatterns[$matchIndex]["Pattern"], $pattern["Pattern"]) !== false) {
                        $matches[] = "Double card pattern: " . $this->getCardLink($pattern["CreditCardID"]) . " '" . $pattern["Pattern"]
                            . "' and " . $this->getCardLink($stringPatterns[$matchIndex]["CreditCardID"]) . " '" . $stringPatterns[$matchIndex]["Pattern"]
                            . "'. <a href=\"" . $this->router->generate('aw_manage_double_patterns', [
                                'pattern1' => $pattern['CreditCardID'] . '-' . $pattern['Pattern'],
                                'pattern2' => $stringPatterns[$matchIndex]["CreditCardID"] . '-' . $stringPatterns[$matchIndex]["Pattern"],
                            ]) . "\" target=\"_blank\">Search matches</a>.";
                    }
                }
            }
        }

        return $matches;
    }

    private function getCardLink(int $cardId): string
    {
        if ($cardId == 0) {
            return "This card";
        }

        return "<a href='?Schema=CreditCard&ID={$cardId}' target='_blank'>{$cardId}</a>";
    }
}
