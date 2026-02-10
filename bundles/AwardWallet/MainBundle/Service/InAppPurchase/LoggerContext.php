<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @NoDI()
 */
class LoggerContext
{
    public static function get(?Usr $user = null): array
    {
        if (!isset($user)) {
            return [];
        }

        $context = [
            "UserID" => $user->getId(),
            "Name" => $user->getFullName(),
            "Level" => $user->getAccountlevel(),
        ];

        $subscription = $user->getSubscription();
        $ppRecProfileId = $user->getPaypalrecurringprofileid();

        if ($subscription) {
            $context['Subscription'] = $subscription;
        }

        if ($ppRecProfileId) {
            $context['PaypalProfileID'] = $ppRecProfileId;
        }

        return $context;
    }
}
