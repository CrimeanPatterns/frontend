<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\PlanNameCreator;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseContainerTest;

use function Codeception\Module\Utils\Reflection\setObjectProperty;

/**
 * @group frontend-unit
 */
class PlanNameTest extends BaseContainerTest
{
    /**
     * @var OperatedByResolver
     */
    protected $operatedByResolver;

    /**
     * @var PlanNameCreator
     */
    private $nameCreator;

    public function _before()
    {
        parent::_before();
        $this->nameCreator = $this->container->get(PlanNameCreator::class);
        $this->operatedByResolver = $this->container->get(OperatedByResolver::class);
    }

    public function _after()
    {
        $this->nameCreator = null;

        parent::_after();
    }

    public function testOnePoint()
    {
        $restaurant = new Restaurant();
        setObjectProperty($restaurant, 'id', 1);
        $restaurant
            ->setAddress('LCY')
            ->setStartdate(new \DateTime('tomorrow'))
            ->setGeotagid(
                (new Geotag())
                    ->setAddress('LCY')
                    ->setLat(51.505268)
                    ->setLng(0.055278)
            );

        $name = $this->nameCreator->generateName(
            $restaurant->getTimelineItems(
                new Usr(),
                QueryOptions::createDesktop()->setOperatedByResolver($this->operatedByResolver)->lock()
            )
        );
        $this->assertEquals("New Travel Plan", $name);
    }

    public function testOneDirection()
    {
        $ts = (new Tripsegment())
            ->setDepartureDate(new \DateTime('tomorrow'))
            ->setDepgeotagid((new Geotag())
            ->setCity('New York')
            ->setLat(40.639751)
            ->setLng(-73.7789))
            ->setArrgeotagid((new Geotag())
            ->setCity('Los Angeles')
            ->setLat(33.94252)
            ->setLng(-118.406998));
        setObjectProperty($ts, 'tripsegmentid', 1);
        $trip = new Trip();
        $trip->addSegment($ts);

        $name = $this->nameCreator->generateName(
            $ts->getTimelineItems(
                new Usr(),
                QueryOptions::createDesktop()->setOperatedByResolver($this->operatedByResolver)->lock()
            )
        );
        $this->assertEquals("Trip to Los Angeles", $name);
    }

    public function testRoundtrip()
    {
        $ts1 = (new Tripsegment())
            ->setMarketingAirlineConfirmationNumber('TESTCN')
            ->setDepartureDate(new \DateTime('-10 day'))
            ->setArrivalDate(new \DateTime('-10 day'))
            ->setDepgeotagid((new Geotag())
            ->setCity('New York')
            ->setLat(40.639751)
            ->setLng(-73.7789))
            ->setArrgeotagid((new Geotag())
            ->setCity('Los Angeles')
            ->setLat(33.94252)
            ->setLng(-118.406998));
        setObjectProperty($ts1, 'tripsegmentid', 1);

        $ts2 = (new Tripsegment())
            ->setMarketingAirlineConfirmationNumber('TESTCN')
            ->setDepartureDate(new \DateTime('-1 day'))
            ->setArrivalDate(new \DateTime('-1 day'))
            ->setDepgeotagid((new Geotag())
            ->setCity('Los Angeles')
            ->setLat(33.94252)
            ->setLng(-118.406998))
            ->setArrgeotagid((new Geotag())
            ->setCity('New York')
            ->setLat(40.639751)
            ->setLng(-73.7789));
        setObjectProperty($ts2, 'tripsegmentid', 2);

        $trip = new Trip();
        $trip
            ->addSegment($ts1)
            ->addSegment($ts2);

        $segments = $trip->getTimelineItems(
            new Usr(),
            QueryOptions::createDesktop()->setOperatedByResolver($this->operatedByResolver)->lock()
        );
        $name = $this->nameCreator->generateName($segments);
        $this->assertEquals("Trip to Los Angeles", $name);
    }
}
