<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\RA\Flight\RequestProgressTracker;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightSearchQuery;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class RequestProgressTrackerTest extends BaseContainerTest
{
    private ?RequestProgressTracker $tracker = null;

    public function _before()
    {
        parent::_before();

        $this->tracker = $this->container->get(RequestProgressTracker::class);
    }

    public function _after()
    {
        $this->tracker = null;

        parent::_after();
    }

    public function test()
    {
        // add query
        $queryId = $this->dbBuilder->makeRAFlightSearchQuery(
            $query = new RAFlightSearchQuery(
                ['JFK'],
                ['LAX'],
                date_create('+7 days'),
                date_create('+14 days'),
                new User()
            )
        );

        $this->tracker->requestStarted('test1', $queryId);
        $this->tracker->requestStarted('test2', $queryId);
        $this->tracker->requestStarted('test3', $queryId);

        $progress = $this->tracker->getProgress($queryId);
        $this->assertEquals([
            'total' => 3,
            'completed' => 0,
            'pending' => 3,
            'timeout' => 0,
            'progress' => 0,
        ], $progress);

        sleep(2);

        $progress = $this->tracker->getProgress($queryId, 1);
        $this->assertEquals([
            'total' => 3,
            'completed' => 3,
            'pending' => 0,
            'timeout' => 3,
            'progress' => 1,
        ], $progress);

        $this->tracker->requestStarted('test4', $queryId);
        $progress = $this->tracker->getProgress($queryId, 1);
        $this->assertEquals([
            'total' => 4,
            'completed' => 3,
            'pending' => 1,
            'timeout' => 3,
            'progress' => 0.75,
        ], $progress);
    }
}
