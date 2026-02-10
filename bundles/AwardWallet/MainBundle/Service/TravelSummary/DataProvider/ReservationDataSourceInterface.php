<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation;

interface ReservationDataSourceInterface
{
    /**
     * Возвращает массив резерваций с точками на карте, у которых нет маршрута.
     *
     * @param Owner $owner объект, содержащий экземпляр текущего пользователя и привязанного пользователя
     * @param \DateTime $startDate начальная дата для выборки
     * @param \DateTime $endDate конечная дата для выборки
     * @return Reservation[]
     */
    public function getData(Owner $owner, \DateTime $startDate, \DateTime $endDate): array;

    /**
     * Returns an array with the number of reservations, grouped by year, for the entire time.
     *
     * @param Owner $owner an object containing an instance of the current user and an attached user
     */
    public function getPeriods(Owner $owner): array;
}
