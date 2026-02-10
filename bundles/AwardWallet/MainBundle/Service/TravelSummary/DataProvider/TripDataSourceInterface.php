<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip;

interface TripDataSourceInterface
{
    /**
     * Возвращает массив резерваций с точками на карте, у которых есть маршрут.
     *
     * @param Owner $owner объект, содержащий экземпляр текущего пользователя и привязанного пользователя
     * @param \DateTime $startDate начальная дата для выборки
     * @param \DateTime $endDate конечная дата для выборки
     * @return Trip[]
     */
    public function getData(Owner $owner, \DateTime $startDate, \DateTime $endDate): array;

    /**
     * Returns an array with the number of trips, grouped by year, for the entire time.
     *
     * @param Owner $owner an object containing an instance of the current user and an attached user
     */
    public function getPeriods(Owner $owner): array;
}
