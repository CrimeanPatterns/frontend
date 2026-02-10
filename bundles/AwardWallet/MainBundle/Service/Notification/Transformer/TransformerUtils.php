<?php

namespace AwardWallet\MainBundle\Service\Notification\Transformer;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @NoDI
 */
class TransformerUtils
{
    /**
     * @param object $entity
     * @return string
     */
    public static function getTimelineKindByEntity($entity)
    {
        switch (get_class($entity)) {
            case Reservation::class: $kind = 'CI';

                break;

            case Rental::class:      $kind = 'PU';

                break;

            case Restaurant::class:  $kind = 'E';

                break;

            case Tripsegment::class: $kind = 'T';

                break;

            case Parking::class:     $kind = 'PS';

                break;

            default:
                throw new \RuntimeException(sprintf('Unknown entity type: "%s"', get_class($entity)));
        }

        return $kind;
    }

    public static function transformRefcode(?Usr $user, string $target): string
    {
        return \preg_replace(
            '#{{\s*refcode\s*}}#',
            $user ? $user->getRefcode() : '',
            $target
        );
    }
}
