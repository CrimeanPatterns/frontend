<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class Normalizer
{
    public function normalize(LoungeInterface $lounge): LoungeNormalized
    {
        return new LoungeNormalized(
            $this->normalizeString($lounge->getAirportCode()),
            $lounge->getName(),
            $this->normalizeString($lounge->getName()),
            $this->normalizeString($lounge->getName(), [
                'lounge', 'airport', 'terminal',
            ]),
            $lounge->getTerminal(),
            $this->normalizeString($lounge->getTerminal()),
            $lounge->getGate(),
            $this->parseGate($lounge->getGate()),
            $lounge->getGate2(),
            $this->parseGate($lounge->getGate2())
        );
    }

    public function normalizeString(?string $string, array $stopWords = []): ?string
    {
        if (StringHandler::isEmpty($string)) {
            return null;
        }

        $string = mb_strtolower($string);
        $string = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);

        // remove stop words
        if ($stopWords) {
            $string = implode(' ', array_diff(explode(' ', $string), $stopWords));
        }

        if (StringHandler::isEmpty($string)) {
            return null;
        }

        return $string;
    }

    private function parseGate(?string $gate): array
    {
        $result = [
            'normalized' => null,
            'prefix' => null,
            'number' => null,
        ];

        if (StringHandler::isEmpty($gate)) {
            return $result;
        }

        if (preg_match('/^(.*?)(\d+)$/ims', $gate, $matches)) {
            return [
                'normalized' => $this->normalizeString($gate),
                'prefix' => $this->normalizeString($matches[1]),
                'number' => (int) $matches[2],
            ];
        }

        return $result;
    }
}
