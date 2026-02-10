<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\RA\Hotel;

use AwardWallet\MainBundle\Service\RA\Hotel\Api;
use AwardWallet\MainBundle\Service\RA\Hotel\ApiResolver;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class HotelsControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $I->verifyMocks();
        parent::_after($I);

        $this->router = null;
    }

    public function testSearchUnauthorized(\TestSymfonyGuy $I)
    {
        $this->logoutUser($I);
        $I->followRedirects(false);
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, []);
        $I->seeResponseCodeIs(302);
    }

    public function testSearchProvidersRequired(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => 'Providers is required',
        ]);
    }

    public function testSearchProvidersMustContainsOnlyStrings(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, ['providers' => [[]]]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => 'Providers must contain only strings',
        ]);
    }

    public function testSearchInvalidProviders(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, false, [
            'provider3' => 'provider3',
        ]);
        $this->sendSearch($I, ['providers' => ['provider1', 'provider2']]);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => 'Invalid providers: "provider1", "provider2"',
        ]);
    }

    /**
     * @dataProvider datesProvider
     */
    public function testSearchValidateDate(\TestSymfonyGuy $I, Example $example)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, array_merge([
            'providers' => ['provider1'],
            'destination' => 'destination',
        ], $example['request']));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => $example['error'],
        ]);
    }

    public function datesProvider(): array
    {
        return [
            [
                'request' => [
                    'checkIn' => null,
                ],
                'error' => 'Check-in date is required',
            ],
            [
                'request' => [
                    'checkIn' => 'abc',
                ],
                'error' => 'Invalid Check-in date',
            ],
            [
                'request' => [
                    'checkIn' => '2021-01-01',
                    'checkOut' => null,
                ],
                'error' => 'Check-out date is required',
            ],
            [
                'request' => [
                    'checkIn' => '2021-01-01',
                    'checkOut' => 'abc',
                ],
                'error' => 'Invalid Check-out date',
            ],
            [
                'request' => [
                    'checkIn' => '2021-01-01',
                    'checkOut' => '2021-01-01',
                ],
                'error' => 'Check-out date must be greater than check-in date',
            ],
        ];
    }

    /**
     * @dataProvider numbersProvider
     */
    public function testSearchValidateNumbers(\TestSymfonyGuy $I, Example $example)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, array_merge([
            'providers' => ['provider1'],
            'destination' => 'destination',
            'checkIn' => '2021-01-01',
            'checkOut' => '2021-01-02',
        ], $example['request']));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => $example['error'],
        ]);
    }

    public function numbersProvider(): array
    {
        return [
            // numberOfRooms
            [
                'request' => [
                    'numberOfRooms' => null,
                ],
                'error' => 'Number of rooms is required',
            ],
            [
                'request' => [
                    'numberOfRooms' => 'abc',
                ],
                'error' => 'Number of rooms is required',
            ],
            [
                'request' => [
                    'numberOfRooms' => 0,
                ],
                'error' => 'Number of rooms is required',
            ],
            [
                'request' => [
                    'numberOfRooms' => -1,
                ],
                'error' => 'Number of rooms must be between 1 and 9',
            ],
            [
                'request' => [
                    'numberOfRooms' => 10,
                ],
                'error' => 'Number of rooms must be between 1 and 9',
            ],

            // numberOfAdults
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => null,
                ],
                'error' => 'Number of adults is required',
            ],
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => 'abc',
                ],
                'error' => 'Number of adults is required',
            ],
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => 0,
                ],
                'error' => 'Number of adults is required',
            ],
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => -1,
                ],
                'error' => 'Number of adults must be between 1 and 9',
            ],
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => 10,
                ],
                'error' => 'Number of adults must be between 1 and 9',
            ],

            // numberOfKids
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => 1,
                    'numberOfKids' => -1,
                ],
                'error' => 'Number of kids must be between 0 and 9',
            ],
            [
                'request' => [
                    'numberOfRooms' => 1,
                    'numberOfAdults' => 1,
                    'numberOfKids' => 10,
                ],
                'error' => 'Number of kids must be between 0 and 9',
            ],
        ];
    }

    public function testSearchChannelNameIsRequired(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, array_merge($this->defaultSearchData(), [
            'channelName' => null,
        ]));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => 'Channel name is required',
        ]);
    }

    public function testSearchDestinationIsRequired(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, array_merge($this->defaultSearchData(), [
            'destination' => null,
        ]));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => 'Destination is required',
        ]);
    }

    public function testSearchSearchIdIsRequired(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, false);
        $this->sendSearch($I, array_merge($this->defaultSearchData(), [
            'searchId' => null,
        ]));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson([
            'error' => 'Search ID is required',
        ]);
    }

    public function testSearchSuccess(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I);
        $this->sendSearch($I, $this->defaultSearchData());
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'requests' => ['request1', 'request2'],
            'steps' => 2,
        ]);
    }

    public function testServiceIsUnavailable(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, true, [
            'provider1' => 'provider1',
            'provider2' => 'provider2',
        ], Expected::exactly(1, function () {
            return [
                'requests' => ['request1', 'request2'],
                'cached' => [],
                'steps' => 0,
                'processed' => 2,
                'errors' => [],
            ];
        }));
        $this->sendSearch($I, $this->defaultSearchData());
        $I->seeResponseCodeIs(503);
        $I->seeResponseContainsJson([
            'error' => 'Service is unavailable',
        ]);
    }

    public function testServiceIsPartiallyUnavailable(\TestSymfonyGuy $I)
    {
        $this->mockApiSearch($I, true, [
            'provider1' => 'provider1',
            'provider2' => 'provider2',
        ], Expected::exactly(1, function () {
            return [
                'requests' => ['request1', 'request2'],
                'cached' => [],
                'steps' => 1,
                'processed' => 2,
                'errors' => [],
            ];
        }));
        $this->sendSearch($I, $this->defaultSearchData());
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'requests' => ['request1', 'request2'],
            'steps' => 1,
            'partial' => true,
        ]);
    }

    private function sendSearch(\TestSymfonyGuy $I, array $data): void
    {
        $I->send('POST', $this->router->generate('aw_hotels_data_search'), $data);
    }

    private function mockApi(
        \TestSymfonyGuy $I,
        array $methods
    ): void {
        $I->mockService(ApiResolver::class, $I->stubMakeEmpty(ApiResolver::class, [
            'getApi' => $I->stubMakeEmpty(Api::class, $methods),
        ]));
    }

    private function mockApiSearch(
        \TestSymfonyGuy $I,
        bool $success = true,
        array $providers = [
            'provider1' => 'provider1',
            'provider2' => 'provider2',
        ],
        ?StubMarshaler $searchResponse = null
    ): void {
        $search = $searchResponse ?? function () {
            return [
                'requests' => ['request1', 'request2'],
                'cached' => [],
                'steps' => 2,
                'processed' => 2,
                'errors' => [],
            ];
        };

        $this->mockApi($I, [
            'getParserList' => $providers,
            'search' => $search,
        ]);
    }

    private function defaultSearchData(): array
    {
        return [
            'providers' => ['provider1', 'provider2'],
            'destination' => 'destination',
            'place_id' => 'abc',
            'checkIn' => '2021-01-01',
            'checkOut' => '2021-01-02',
            'numberOfRooms' => 1,
            'numberOfAdults' => 2,
            'numberOfKids' => 3,
            'channelName' => 'channelName',
            'searchId' => 'searchId',
        ];
    }
}
