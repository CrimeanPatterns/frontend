<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher;

class RegexMetadataFactory
{
    /**
     * @return array<array>
     */
    public static function create(string $patterns): array
    {
        $merchantPatternsList = [];

        foreach (explode("\n", $patterns) as $item) {
            $original = $item;
            $item = trim($item);
            [$item, $isPositive] = self::processSign($item);
            $originalWithoutSign = $item;
            $isPreg = \substr($item, 0, 1) === '#';
            $beginSymbol = \substr($item, 1, 1) === '^';
            $pregError = null;

            if ($isPreg) {
                try {
                    $isPreg = (false !== \preg_match($item, 'somesubject'));
                } catch (\ErrorException $e) {
                    $pregError = $e->getMessage();
                }
            }

            $merchantPatternsList[] = [
                'original' => $original,
                'originalWithoutSign' => $originalWithoutSign,
                'template' => $item,
                'isPreg' => $isPreg,
                'isPositive' => $isPositive,
                'beginSymbol' => $beginSymbol,
                'pregError' => $pregError,
            ];
        }

        return $merchantPatternsList;
    }

    /**
     * @return array{0: string, 1: bool}
     */
    public static function processSign(string $item): array
    {
        $isPositive = true;

        // check escaped + and - first
        if (
            (
                ($isMinus = (\substr($item, 0, 1) === '-'))
                && (\substr($item, 0, 2) !== '\\-')
            )
            || (
                (\substr($item, 0, 1) === '+')
                && (\substr($item, 0, 2) !== '\\+')
            )
        ) {
            $item = \substr($item, 1);
            $isPositive = !$isMinus;
        }

        return [$item, $isPositive];
    }
}
