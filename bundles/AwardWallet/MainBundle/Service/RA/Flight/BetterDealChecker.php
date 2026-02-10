<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use Psr\Log\LoggerInterface;

class BetterDealChecker
{
    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor([
            'class' => 'BetterDealChecker',
        ]));
    }

    public function isBetterDeal(int $newMileCost, int $oldMileCost, float $newTTTHours, float $oldTTTHours): bool
    {
        $context = [
            'newMileCost' => $newMileCost,
            'oldMileCost' => $oldMileCost,
            'newTTTHours' => $newTTTHours,
            'oldTTTHours' => $oldTTTHours,
        ];

        if ($newMileCost == 0 || $oldMileCost == 0 || $newTTTHours == 0 || $oldTTTHours == 0) {
            $this->logger->info('invalid deal parameters', $context);

            return false;
        }

        // Mile cost condition: new cost <= 80% of old AND difference >= 5000
        $mileCostCondition = ($newMileCost <= 0.8 * $oldMileCost) && ($oldMileCost - $newMileCost >= 5000);

        // Duration condition: (new <= 70% of old AND diff >= 1h) OR (diff >= 2.5h)
        $timeDifference = $oldTTTHours - $newTTTHours;
        $durationCondition =
            (($newTTTHours <= 0.7 * $oldTTTHours) && $timeDifference >= 1)
            || ($timeDifference >= 2.5);
        $context['mileCostCondition'] = $mileCostCondition;
        $context['durationCondition'] = $durationCondition;

        // If mile cost is good
        if ($mileCostCondition) {
            // Accept if time is not worse or only slightly worse (<=30min)
            if ($newTTTHours <= $oldTTTHours || ($newTTTHours - $oldTTTHours) <= 0.5) {
                $this->logger->info('better deal found based on mile cost', $context);

                return true;
            }
        }

        // If duration is significantly better and cost is not worse
        if ($durationCondition && $newMileCost <= $oldMileCost) {
            $this->logger->info('better deal found based on duration', $context);

            return true;
        }

        $this->logger->info('better deal check failed', $context);

        return false;
    }
}
