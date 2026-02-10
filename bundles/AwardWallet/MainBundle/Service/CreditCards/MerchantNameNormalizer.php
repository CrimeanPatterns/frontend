<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

class MerchantNameNormalizer
{
    private const DO_NOT_NORMALIZE = ["NORDSTROM"];

    // нормализует описание транзакции для идентификации мерчанта
    public function normalize(string $description): string
    {
        $result = strtoupper(html_entity_decode($description));

        foreach (self::DO_NOT_NORMALIZE as $exclude) {
            if (false !== strpos($result, $exclude)) {
                return $result;
            }
        }

        $result = trim(preg_replace(['/\W/', '/\d{3,}/', '/\s{2,}/', '/\bX{3,}\b/', '/\bACCOUNT NUMBER\b/'], [' ', '', ' ', '', ''], $result));

        return $result;
    }
}
