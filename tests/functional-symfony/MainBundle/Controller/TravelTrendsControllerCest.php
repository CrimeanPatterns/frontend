<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Command\TravelStatisticsCommand;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class TravelTrendsControllerCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Router */
    private $router;

    /** @var array */
    private $dayPeriods;

    /** @var array */
    private $monthPeriods;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
        $this->dayPeriods = $this->fetchDatePeriods(true);
        $this->monthPeriods = $this->fetchDatePeriods(false);

        $cacheData = [];
        $cacheData = $this->baseData($cacheData);
        $cacheData = $this->generateNumberReservationData($cacheData);
        $cacheData = $this->generateLongHaulData($cacheData);
        $cacheData = $this->generateMilePointEarningData($cacheData);

        $stringData = serialize($cacheData);
        $I->haveS3Object(TravelStatisticsCommand::BUCKET, TravelStatisticsCommand::CACHE_KEY . ':countryus', $stringData);
        $I->seeS3Object(TravelStatisticsCommand::BUCKET, TravelStatisticsCommand::CACHE_KEY . ':countryus', $stringData);

        $user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS], true));
        $I->amOnPage($this->router->generate('aw_charts_travel_trends', ['_switch_user' => $user->getLogin()]));
    }

    public function numberReservationDataTest(\TestSymfonyGuy $I)
    {
        $I->sendAjaxPostRequest($this->router->generate('aw_charts_travel_trends_numberReservation_data'));
        $json = json_decode($I->grabResponse(), true);

        $I->assertEquals(count($json['chart']['data']['labels']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);

        foreach ($json['chart']['data']['datasets'] as $data) {
            $I->assertEquals(count($data['data']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);
        }

        $I->sendAjaxPostRequest($this->router->generate('aw_charts_travel_trends_numberReservation_data', [
            'dateType' => TravelStatisticsCommand::PERIOD_MONTH,
            'providerType' => TravelStatisticsCommand::TYPE_FLIGHTS,
            'long' => true,
            'short' => true,
            'stack' => false,
        ]));
        $json = json_decode($I->grabResponse(), true);

        $I->assertEquals(count($json['chart']['data']['labels']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);

        foreach ($json['chart']['data']['datasets'] as $data) {
            $I->assertEquals(count($data['data']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);
        }
    }

    public function longHaulDataTest(\TestSymfonyGuy $I)
    {
        $I->sendAjaxPostRequest($this->router->generate('aw_charts_travel_trends_longHaul_data'));
        $json = json_decode($I->grabResponse(), true);

        $I->assertEquals(count($json['chart']['data']['labels']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);

        foreach ($json['chart']['data']['datasets'] as $data) {
            $I->assertEquals(count($data['data']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);
        }

        $I->assertEquals('Short Haul', $json['chart']['data']['datasets'][0]['label']);
        $I->assertEquals('Long Haul', $json['chart']['data']['datasets'][1]['label']);
    }

    public function milePointEarningsDataTest(\TestSymfonyGuy $I)
    {
        $I->sendAjaxPostRequest($this->router->generate('aw_charts_travel_trends_earningRedemption_data'));
        $json = json_decode($I->grabResponse(), true);

        $I->assertEquals(count($json['chart']['data']['labels']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);

        foreach ($json['chart']['data']['datasets'] as $data) {
            $I->assertEquals(count($data['data']) - 1, TravelStatisticsCommand::PERIOD_MONTH_COUNT);
        }
    }

    private function baseData(array $cacheData): array
    {
        $cacheData['usersCount'] = [
            'all' => 0,
            TravelStatisticsCommand::PERIOD_DAY => [],
            TravelStatisticsCommand::PERIOD_MONTH => [],
        ];
        $cacheData['usersCount']['all'] = rand(5, 100);

        foreach ($this->dayPeriods as $period) {
            $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_DAY][$period] = rand(5, 100);
        }

        foreach ($this->monthPeriods as $period) {
            $cacheData['usersCount'][TravelStatisticsCommand::PERIOD_MONTH][$period] = rand(5, 100);
        }

        return $cacheData;
    }

    private function generateNumberReservationData(array $cacheData): array
    {
        $cacheData['type'] = [
            TravelStatisticsCommand::PERIOD_DAY => [
                TravelStatisticsCommand::TYPE_FLIGHTS => [],
                TravelStatisticsCommand::TYPE_HOTELS => [],
                TravelStatisticsCommand::TYPE_RENTED_CARS => [],
            ],
            TravelStatisticsCommand::PERIOD_MONTH => [
                TravelStatisticsCommand::TYPE_FLIGHTS => [],
                TravelStatisticsCommand::TYPE_HOTELS => [],
                TravelStatisticsCommand::TYPE_RENTED_CARS => [],
            ],
        ];
        $cacheData['provider'] = $cacheData['type'];

        foreach ($this->dayPeriods as $period) {
            $cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS][$period] = rand(5, 100);
            $cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_HOTELS][$period] = rand(5, 100);
            $cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_RENTED_CARS][$period] = rand(5, 100);
        }

        foreach ($this->monthPeriods as $period) {
            $cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][$period] = rand(5, 100);
            $cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_HOTELS][$period] = rand(5, 100);
            $cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_RENTED_CARS][$period] = rand(5, 100);
        }

        for ($i = 0, $iCount = rand(1, 4); $i < $iCount; $i++) {
            unset($cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS])]);
            unset($cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS])]);
            unset($cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData['type'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS])]);

            unset($cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS])]);
            unset($cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS])]);
            unset($cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData['type'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS])]);
        }

        foreach (TravelStatisticsCommand::FLIGHTS_OPERATING_AIRLINE_ID as $providerId => $provider) {
            $cacheData['provider'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS][$providerId] = [];

            foreach ($this->dayPeriods as $period) {
                $cacheData['provider'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_FLIGHTS][$providerId][$period] = rand(5, 100);
            }

            foreach ($this->monthPeriods as $period) {
                $cacheData['provider'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][$providerId][$period] = rand(5, 100);
            }
        }

        foreach (TravelStatisticsCommand::HOTELS_PROVIDER_ID as $providerId => $provider) {
            $cacheData['provider'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_HOTELS][$providerId] = [];

            foreach ($this->dayPeriods as $period) {
                $cacheData['provider'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_HOTELS][$providerId][$period] = rand(5, 100);
            }

            foreach ($this->monthPeriods as $period) {
                $cacheData['provider'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_HOTELS][$providerId][$period] = rand(5, 100);
            }
        }

        foreach (TravelStatisticsCommand::RENTED_CARS_PROVIDER_ID as $providerId => $provider) {
            $cacheData['provider'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_RENTED_CARS][$providerId] = [];

            foreach ($this->dayPeriods as $period) {
                $cacheData['provider'][TravelStatisticsCommand::PERIOD_DAY][TravelStatisticsCommand::TYPE_RENTED_CARS][$providerId][$period] = rand(5, 100);
            }

            foreach ($this->monthPeriods as $period) {
                $cacheData['provider'][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_RENTED_CARS][$providerId][$period] = rand(5, 100);
            }
        }

        return $cacheData;
    }

    private function generateLongHaulData(array $cacheData): array
    {
        $cacheData[TravelStatisticsCommand::TYPE_LONGHAUL] = [
            TravelStatisticsCommand::PERIOD_MONTH => [
                TravelStatisticsCommand::TYPE_FLIGHTS => [],
            ],
        ];

        foreach ($this->monthPeriods as $period) {
            $cacheData[TravelStatisticsCommand::TYPE_LONGHAUL][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][$period] = [
                'short' => rand(5, 500),
                'long' => rand(5, 500),
                'diff' => 0,
            ];
        }

        unset($cacheData[TravelStatisticsCommand::TYPE_LONGHAUL][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData[TravelStatisticsCommand::TYPE_LONGHAUL][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS])]);
        unset($cacheData[TravelStatisticsCommand::TYPE_LONGHAUL][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS][array_rand($cacheData[TravelStatisticsCommand::TYPE_LONGHAUL][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_FLIGHTS])]);

        return $cacheData;
    }

    private function generateMilePointEarningData(array $cacheData): array
    {
        $cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY] = [
            TravelStatisticsCommand::PERIOD_MONTH => [
                TravelStatisticsCommand::TYPE_TOTAL_BANKS => [],
                TravelStatisticsCommand::TYPE_TOTAL_HOTELS => [],
                TravelStatisticsCommand::TYPE_TOTAL_AIRLINES => [],
            ],
        ];

        foreach ($this->monthPeriods as $period) {
            $cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_BANKS][$period] = [
                'earnings' => rand(5, 1000),
                'redemptions' => rand(5, 1000),
            ];
            $cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_HOTELS][$period] = [
                'earnings' => rand(5, 1000),
                'redemptions' => rand(5, 1000),
            ];
            $cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_AIRLINES][$period] = [
                'earnings' => rand(5, 1000),
                'redemptions' => rand(5, 1000),
            ];
        }

        for ($i = 0, $iCount = 5; $i < $iCount; $i++) {
            unset($cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_BANKS][array_rand($cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_BANKS])]);
            unset($cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_HOTELS][array_rand($cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_HOTELS])]);
            unset($cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_AIRLINES][array_rand($cacheData[TravelStatisticsCommand::TOTALLY_EARNING_MP_DATA_KEY][TravelStatisticsCommand::PERIOD_MONTH][TravelStatisticsCommand::TYPE_TOTAL_AIRLINES])]);
        }

        return $cacheData;
    }

    private function fetchDatePeriods(bool $isDaily): array
    {
        $period = [];
        $date = new \DateTimeImmutable();
        $date->setTime(0, 0, 0);

        if ($isDaily) {
            $count = TravelStatisticsCommand::PERIOD_DAY_COUNT;
            $date->modify('-' . $count . ' days');

            for ($i = $count; $i > 0; $i--) {
                $period[] = $date->modify('-' . $i . ' day')->format('Y-m-d');
            }

            return $period;
        }

        $count = TravelStatisticsCommand::PERIOD_MONTH_COUNT;
        $date->modify('-' . $count . ' months');

        for ($i = $count; $i > 0; $i--) {
            $period[] = (new \DateTimeImmutable('@' . strtotime(date('Y-m-01') . " -$i months")))->format('Y-m');
        }

        return $period;
    }
}
