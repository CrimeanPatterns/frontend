<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CustomLoyaltyProperty;
use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\LocationContainerInterface;
use AwardWallet\MainBundle\Entity\LocationSetting;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\BarcodeCreatorFactory;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\StoreLocationFinder\PlaceFinder;
use AwardWallet\MainBundle\Service\StoreLocationFinder\StoreFilter;
use AwardWallet\MainBundle\Service\StoreLocationFinder\StoreLocationFinder;
use AwardWallet\Tests\Unit\BaseUserTest;
use Monolog\Logger;
use Prophecy\Argument;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNotNull;

/**
 * @group frontend-unit
 * @group mobile
 */
class StoreLocationFinderTest extends BaseUserTest
{
    protected const GEO_OFFSET = 0.001;
    /**
     * @var float
     */
    protected $baseLat;
    /**
     * @var float
     */
    protected $baseLng;

    public function _before()
    {
        parent::_before();
        $zip = $this->em->getConnection()->executeQuery('select `Zip`, `Lat`, `Lng` from `ZipCode` limit 1')->fetch(\PDO::FETCH_ASSOC);
        assertNotEmpty($zip, 'No valid zipcode!');
        ['Lat' => $this->baseLat, 'Lng' => $this->baseLng] = $zip;
        $this->user
            ->setZip($zip['Zip'])
            ->setZipCodeUpdateDate(new \DateTime())
            ->setCountry('U.S.A.');

        $this->em->flush($this->user);
    }

    public function testNoAvailableSlots()
    {
        $provider = $this
            ->createProvider()
            ->setLoginurl('http://www.ya.ru')
            ->setKind(PROVIDER_KIND_SHOPPING);

        $account = (new Account())
            ->setUserid($this->user)
            ->setLogin('abc')
            ->setProviderid($provider);

        $this->em->persist((new CustomLoyaltyProperty('BarCodeData', 'someval'))->setContainer($account));
        $this->em->persist($account);

        foreach (range(1, 20) as $i) {
            $this->em->persist(location($account, "some name {$i}", $this->baseLat, $this->baseLng));
        }

        $this->em->flush();

        $this
            ->getLocationFinder()
            ->findLocationsNearZipArea(
                (new StoreFilter())
                    ->setUserIds([$this->user->getUserid()])
                    ->setLocationsLimit(20)
                    ->setRadius(100),
                false
            );

        $this->assertLocations($this->user,
            it(\iter\range(1, 20))
                ->map(function ($i) use ($account) { return location($account, "some name {$i}", $this->baseLat, $this->baseLng); })
                ->toArray()
        );

        $this->em->refresh($account);
        assertNotNull($account->getLastStoreLocationUpdateDate());
    }

    /**
     * @dataProvider providerKindProvider
     */
    public function testOnePointForOneAccount(int $providerKind)
    {
        $provider = $this
            ->createProvider()
            ->setLoginurl('http://www.ya.ru')
            ->setKind($providerKind);

        $account = (new Account())
            ->setUserid($this->user)
            ->setLogin('abc')
            ->setProviderid($provider);

        $scannedBarcode =
            (new CustomLoyaltyProperty('BarCodeData', 'someval'))
                ->setContainer($account);
        $this->em->persist($scannedBarcode);
        $this->em->persist($account);
        $this->em->flush();

        $name = $provider->getShortname() . ' www.ya.ru';
        $this
            ->getLocationFinder([
                'named' => [
                    $name => [
                        googlePlace($name, $this->baseLat, $this->baseLng, 'Lenin st.'),
                    ],
                ],
            ])
            ->findLocationsNearZipArea(
                (new StoreFilter())
                    ->setUserIds([$this->user->getUserid()])
                    ->setLocationsLimit(20)
                    ->setRadius(100),
                false
            );

        $this->assertLocations($this->user,
            [
                location($account, "{$name}, Lenin st.", $this->baseLat, $this->baseLng, $this->user),
            ]
        );
    }

    public function providerKindProvider()
    {
        return [
            ['kind' => PROVIDER_KIND_SHOPPING],
            ['kind' => PROVIDER_KIND_DINING],
        ];
    }

    public function testMultiplePointsWithLimitPerLoyaltyGroupForOneAccount()
    {
        $provider = $this
            ->createProvider()
            ->setLoginurl('http://www.ya.ru')
            ->setKind(PROVIDER_KIND_SHOPPING);

        $account = (new Account())
            ->setUserid($this->user)
            ->setLogin('abc')
            ->setProviderid($provider);

        $scannedBarcode =
            (new CustomLoyaltyProperty('BarCodeData', 'someval'))
                ->setContainer($account);
        $this->em->persist($scannedBarcode);
        $this->em->persist($account);
        $this->em->flush();

        $name = $provider->getShortname() . ' www.ya.ru';
        $this
            ->getLocationFinder([
                'named' => [
                    $name => [
                        googlePlace($name, $this->baseLat + 1 * self::GEO_OFFSET, $this->baseLng + 1 * self::GEO_OFFSET, 'Lenin st. 1'),
                        googlePlace($name, $this->baseLat + 2 * self::GEO_OFFSET, $this->baseLng + 2 * self::GEO_OFFSET, 'Lenin st. 2'),
                        googlePlace($name, $this->baseLat + 3 * self::GEO_OFFSET, $this->baseLng + 3 * self::GEO_OFFSET, 'Lenin st. 3'),
                        googlePlace($name, $this->baseLat + 4 * self::GEO_OFFSET, $this->baseLng + 3 * self::GEO_OFFSET, 'Lenin st. 4'),
                    ],
                ],
            ])
            ->findLocationsNearZipArea(
                (new StoreFilter())
                    ->setUserIds([$this->user->getUserid()])
                    ->setLocationsLimit(20)
                    ->setRadius(100)
                    ->setLoyaltyLimitPerGroup(3),
                false
            );

        $this->assertLocations($this->user,
            [
                location($account, "{$name}, Lenin st. 1", $this->baseLat + 1 * self::GEO_OFFSET, $this->baseLng + 1 * self::GEO_OFFSET, $this->user),
                location($account, "{$name}, Lenin st. 2", $this->baseLat + 2 * self::GEO_OFFSET, $this->baseLng + 2 * self::GEO_OFFSET, $this->user),
                location($account, "{$name}, Lenin st. 3", $this->baseLat + 3 * self::GEO_OFFSET, $this->baseLng + 3 * self::GEO_OFFSET, $this->user),
            ]
        );
    }

    public function testMultiplePointsForMultipleProviders()
    {
        $places = ['named' => []];
        $accounts = [];

        foreach (range(1, 20) as $i) {
            $provider = $this
                ->createProvider()
                ->setLoginurl("http://www.ya{$i}.ru")
                ->setKind(PROVIDER_KIND_SHOPPING)
                ->setAccounts($i * 1000);

            $account = (new Account())
                ->setUserid($this->user)
                ->setLogin('abc')
                ->setProviderid($provider);

            $scannedBarcode =
                (new CustomLoyaltyProperty('BarCodeData', 'someval'))
                    ->setContainer($account);
            $this->em->persist($scannedBarcode);
            $this->em->persist($account);

            $name = $provider->getShortname() . " www.ya{$i}.ru";

            foreach (range(1, 20) as $j) {
                $places['named'][$name][] = googlePlace($name, $this->baseLat + $j * self::GEO_OFFSET, $this->baseLng + $j * self::GEO_OFFSET, "Lenin st. {$i}");
            }

            $accounts[$name] = $account;
        }

        $this->em->flush();

        $this
            ->getLocationFinder($places)
            ->findLocationsNearZipArea(
                (new StoreFilter())
                    ->setUserIds([$this->user->getUserid()])
                    ->setLocationsLimit(20)
                    ->setRadius(100),
                false
            );

        $expected = [];
        $i = 1;

        foreach ($accounts as $name => $account) {
            $expected[] = location($account, "{$name}, Lenin st. {$i}", $this->baseLat + 1 * self::GEO_OFFSET, $this->baseLng + 1 * self::GEO_OFFSET, $this->user);
            $i++;
        }

        $this->assertLocations($this->user, $expected);
    }

    public function testStoreLocationSearchShouldNeverBePerformedAgain()
    {
        $provider = $this
            ->createProvider()
            ->setLoginurl('http://www.ya.ru')
            ->setKind(PROVIDER_KIND_SHOPPING);

        $account = (new Account())
            ->setUserid($this->user)
            ->setLogin('abc')
            ->setProviderid($provider)
            ->setLastStoreLocationUpdateDate(new \DateTime('-1 month'));

        $scannedBarcode =
            (new CustomLoyaltyProperty('BarCodeData', 'someval'))
                ->setContainer($account);
        $this->em->persist($scannedBarcode);
        $this->em->persist($account);
        $this->em->flush();

        $name = $provider->getShortname() . ' www.ya.ru';
        $this
            ->getLocationFinder(
                [],
                [],
                $this->prophesize(PlaceFinder::class)
                    ->getNearbyPlacesByNameIter(Argument::any())->shouldNotBeCalled()
                    ->getObjectProphecy()
                    ->getPlacesByAddressIter(Argument::any())->shouldNotBeCalled()
                    ->getObjectProphecy()
                    ->reveal()
            )
            ->findLocationsNearZipArea(
                (new StoreFilter())
                    ->setUserIds([$this->user->getUserid()])
                    ->setLocationsLimit(20)
                    ->setRadius(100),
                false
            );

        $this->assertLocations($this->user, []);
    }

    protected function createProvider(): Provider
    {
        $provider = $this->em->getRepository(Provider::class)->find($this->aw->createAwProvider());

        return $provider->setShortname($provider->getDisplayname());
    }

    protected function assertLocations(Usr $user, array $expectedLocations)
    {
        $existingLocations = $this->em->getRepository(Location::class)->getLocationsByUser($user)->fetchAll();
        $existingLocationsMap = [];

        foreach ($existingLocations as $existingLocation) {
            $type =
                ((strpos($existingLocation['AccountType'], 'Account') === 0) ? 'Account' :
                    ((strpos($existingLocation['AccountType'], 'SubAccount') === 0) ? 'SubAccount' :
                        'Coupon'
                    )
                );

            $existingLocationsMap[
                $type . "." .
                $existingLocation['ShortAccountID'] .
                '.Name:' .
                $existingLocation['LocationName']
            ] = [
                'name' => $existingLocation['LocationName'],
                'lat' => (float) $existingLocation['Lat'],
                'lng' => (float) $existingLocation['Lng'],
                'radius' => (int) $existingLocation['Radius'],
                'tracked' => (bool) $existingLocation['Tracked'],
            ];
        }

        $expectedLocationsMap = [];

        foreach ($expectedLocations as $expectedLocation) {
            $location = ($expectedLocation instanceof LocationSetting) ?
                $expectedLocation->getLocation() :
                $expectedLocation;

            $container = $location->getContainer();
            $key =
                ($container instanceof Account ? 'Account.' . $container->getId() :
                    ($container instanceof Subaccount ? 'SubAccount.' . $container->getAccountid()->getId() . '.' . $container->getId() :
                        'Coupon.' . $container->getId()
                    )
                );

            $expectedLocationsMap[
                $key .
                '.Name:' .
                $location->getName()
            ] = [
                'name' => $location->getName(),
                'lat' => $location->getLat(),
                'lng' => $location->getLng(),
                'radius' => $location->getRadius(),
                'tracked' => $location
                        ->getLocationSettings()
                        ->filter(function (LocationSetting $locationSetting) use ($user) {
                            return
                                ($locationSetting->getUser()->getUserid() === $user->getUserid())
                                && $locationSetting->isTracked()
                            ;
                        })
                        ->count() === 1,
            ];
        }

        ksort($expectedLocationsMap);
        ksort($existingLocationsMap);

        assertEquals($expectedLocationsMap, $existingLocationsMap, 'locations does not match');
    }

    protected function getPlaceFinderMock(array $data = []): PlaceFinder
    {
        $prophecy = $this->prophesize(PlaceFinder::class);

        $prophecy->getNearbyPlacesByNameIter(Argument::cetera())
            ->will(function (array $args) use ($data) {
                [$lat, $lng, $radius, $options] = $args;

                return new \ArrayIterator($data['named'][$options['keyword'] ?? ''] ?? []);
            });

        $prophecy->getPlacesByAddressIter(Argument::cetera())
            ->will(function (array $args) use ($data) {
                [$name] = $args;

                return new \ArrayIterator($data['address'][$name] ?? []);
            });

        return $prophecy->reveal();
    }

    protected function getLocationFinder(array $placesData = [], array $providersWithLocations = [], ?PlaceFinder $placeFinder = null): StoreLocationFinder
    {
        return new StoreLocationFinder(
            $placeFinder ?: $this->getPlaceFinderMock($placesData),
            $this->container->get('doctrine.orm.default_entity_manager'),
            $this->container->get('database_connection'),
            new Logger('store'),
            $this->em->getRepository(Usr::class),
            $this->em->getRepository(Location::class),
            $this->prophesize(CacheManager::class)->reveal(),
            $this->container->get(BarcodeCreatorFactory::class),
            $providersWithLocations
        );
    }
}

function location(LocationContainerInterface $locationContainer, string $name, ?float $lat = null, ?float $lng = null, ?Usr $locationSettingViewer = null, bool $tracked = true, bool $generated = false): Location
{
    $location = (new Location())
        ->setName($name)
        ->setLat($lat)
        ->setLng($lng)
        ->setRadius(100)
        ->setContainer($locationContainer)
        ->setGenerated($generated);

    if (isset($locationSettingViewer)) {
        $location->addLocationSettings(new LocationSetting($location, $locationSettingViewer, $tracked));
    }

    return $location;
}

function locationsSetting(Location $location, Usr $user, bool $tracked): LocationSetting
{
    return new LocationSetting($location, $user, $tracked);
}

function googlePlace(string $name, string $lat, string $lng, ?string $address = null): array
{
    return [
        'name' => $name,
        'geometry' => [
            'location' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
        ],
        'vicinity' => $address ?? $name,
        'formatted_address' => $address ?? $name,
    ];
}
