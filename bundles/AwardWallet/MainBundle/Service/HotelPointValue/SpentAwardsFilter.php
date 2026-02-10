<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use Psr\Log\LoggerInterface;

class SpentAwardsFilter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function filter(?string $spentAwards): ?int
    {
        if ($spentAwards === null || trim($spentAwards) === '') {
            return null;
        }

        $original = $spentAwards;

        // remove cents, like "12.45" => "12"
        $spentAwards = preg_replace('#[\.\,]\d{2}([\b$]|[^\d])#uims', '\1', $spentAwards);

        // remove thousand separators, like "100,000" => "100000"
        $spentAwards = preg_replace('#(\d)[\.\, ]+(\d)#uims', '\1\2', $spentAwards);

        // remove all but + N points, like "2176 ₽ + 10000 points" => "+ 10000 points"
        $spentAwards = preg_replace('#^.*([+-]\s*\d+\s*points).*$#uims', '\1', $spentAwards);

        // remove trailing currency part, like "661 € + 292000 points" => "+ 292.000 points"
        $spentAwards = preg_replace('#(\d+)\s*(€|$|￡|kr|Kč)\s*([^\d]|[\b$])#uims', '\3', $spentAwards);

        // remove leading currency part, like "$661 + 292000 points" => "+ 292.000 points"
        $spentAwards = preg_replace('#(€|$|￡)\s*\d+#uims', '', $spentAwards);

        // remove brand names, like "1,889 Expedia Rewards points" => "1,889 rewards points"
        $spentAwards = preg_replace('#(\d+\s+)(\w+\s+)+(reward|rewards)?\s+(points)#uims', '\1\3', $spentAwards);

        // remove "reward", "rewards", "points", "pts."
        $spentAwards = preg_replace('#(^|\b)(rewards|reward|points|pts\.|pts|miles|pontos)($|\b)#uims', '', $spentAwards);

        // trim spaces and plus
        $spentAwards = preg_replace('#\s{2,}}#uims', ' ', $spentAwards);
        $spentAwards = trim($spentAwards, ' +');

        if (preg_match('#^(\d+)\s*\+\s*(\d+)$#uims', $spentAwards, $matches)) {
            return (int) $matches[1] + (int) $matches[2];
        }

        $result = (int) $spentAwards;

        if ((string) $result !== $spentAwards) {
            $this->logger->warning("failed to convert spentAwards. original: {$original}, filtered: {$spentAwards}, result: {$result}");

            return null;
        }

        return $result;
    }
}
