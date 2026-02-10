<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Reservation;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI()
 */
class ConsoleTable
{
    public static function render(array $reservations, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setRows(it($reservations)
            ->map(function (Reservation $reservation) {
                return [
                    "ID" => $reservation->getId(),
                    "SpentAwards" => $reservation->getPricingInfo()->getSpentAwards(),
                    "Cost" => $reservation->getPricingInfo()->getCost(),
                    "Tax" => $reservation->getPricingInfo()->getTax(),
                    "Total" => $reservation->getPricingInfo()->getTotal(),
                    "Currency" => $reservation->getPricingInfo()->getCurrencyCode(),
                    "HpvTotalPointsSpent" => $reservation->getHotelPointValue() ? $reservation->getHotelPointValue()->getTotalPointsSpent() : null,
                    "HpvTotalTaxesSpent" => $reservation->getHotelPointValue() ? $reservation->getHotelPointValue()->getTotalTaxesSpent() : null,
                    "Note" => $reservation->getHotelPointValue() ? $reservation->getHotelPointValue()->getNote() : '',
                ];
            })
            ->toArray()
        );
        $table->setHeaders(["ID", "SpentAwards", "Cost", "Tax", "Total", "Currency", "HpvTotalPointsSpent", "HpvTotalTaxesSpent", "Note"]);
        $table->render();
    }
}
