<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Provider;

/**
 * @NoDI()
 */
class MultiplierService
{
    public static function calculate(float $amount, float $miles, int $providerId): ?float
    {
        if (abs(round($amount)) < 0.001) {
            return null;
        }

        switch ($providerId) {
            case Provider::CHASE_ID:
                $multiplier = round($miles / $amount, 1);

                break;

            case Provider::CITI_ID:
            case Provider::CAPITAL_ONE_ID:
                $multiplier = round($miles / $amount, 1);

                if (fmod($multiplier, 0.5) !== 0) {
                    $multiplier = round($multiplier);
                }

                break;

            case Provider::AMEX_ID:
                $multiplier = round(abs($miles) / round(abs($amount)), 1);

                break;

            default:
                $multiplier = round(round(abs($miles) / abs($amount) / 0.05) * 0.05, 2);

                // fmod(1.25, 0.05) == 0.05. strange behavior. so, not using fmod
                //                if (abs($multiplier - floor($multiplier / 0.05) * 0.05) >= 0.005) {
                //                    $multiplier = round($multiplier, 1);
                //                }

                break;
        }

        if (abs($multiplier) > 10) {
            $multiplier = 0;
        }

        return $multiplier;
    }
}
