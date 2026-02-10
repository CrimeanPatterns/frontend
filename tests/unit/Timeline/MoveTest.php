<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;
use Doctrine\Common\Collections\Criteria;

/**
 * @group frontend-unit
 */
class MoveTest extends BaseUserTest
{
    /**
     * @var Manager
     */
    private $manager;

    public function _before()
    {
        parent::_before();
        $this->manager = $this->container->get(Manager::class);
    }

    public function testInvalidItineraryCode()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid itinerary code');
        $this->manager->moveItinerary("W.11111111", null, 1);
    }

    public function testItineraryNotFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Itinerary not found');
        $this->manager->moveItinerary("T.1111111111111111", null, 1);
    }

    public function testAccessDenied()
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $tripseg = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->findOneBy([]);
        $this->assertNotEmpty($tripseg);
        $this->manager->moveItinerary("T." . $tripseg->getTripsegmentid(), null, 1);
    }

    public function testMoveAndCopy()
    {
        $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $account = $this->getAccount('future.trip.random.props', "");
        $this->em->persist($account);
        $this->em->flush();
        $this->aw->checkAccount($account->getAccountid(), true);
        $trip = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Trip::class)
            ->findOneBy(["account" => $account]);
        /** @var Tripsegment $tripseg */
        $tripseg = $trip->getSegments()->first();
        $tripsegId = "T." . $tripseg->getTripsegmentid();

        $newUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->aw->createAwUser('new-owner-' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, []));
        $newBusinessUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->aw->createAwUser('new-owner-bus-' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [
            "AccountLevel" => ACCOUNT_LEVEL_BUSINESS,
        ]));
        $bua1 = $uaRep->find($this->aw->createConnection($newUser->getUserid(), $newBusinessUser->getUserid(), true, true, [
            "AccessLevel" => ACCESS_WRITE,
        ]));
        $bua2 = $uaRep->find($this->aw->createConnection($newBusinessUser->getUserid(), $newUser->getUserid(), true, true, [
            "AccessLevel" => ACCESS_ADMIN,
            'TripAccessLevel' => TRIP_ACCESS_FULL_CONTROL,
        ]));
        $this->aw->shareAwTimeline($newBusinessUser->getUserid(), null, $newUser->getUserid());

        // connections
        $ua1 = $uaRep->find($this->aw->createConnection($newUser->getUserid(), $this->user->getUserid(), true, true, [
            "AccessLevel" => ACCESS_WRITE,
            'TripAccessLevel' => TRIP_ACCESS_FULL_CONTROL,
        ]));
        $this->aw->shareAwTimeline($newUser->getUserid(), null, $this->user->getUserid());
        $ua2 = $uaRep->find($this->aw->createConnection($this->user->getUserid(), $newUser->getUserid(), true, true, [
            "AccessLevel" => ACCESS_WRITE,
        ]));
        $ua3 = $uaRep->find($this->aw->createFamilyMember($this->user->getUserid(), 'Billy', 'Villy'));

        // ################ tests ##################
        try {
            // other ua, access denied
            $catched = false;
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->neq('agentid', $this->user));
            $criteria->andWhere($criteria->expr()->isNull('clientid'));
            $criteria->setMaxResults(1);
            $otherUa = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->matching($criteria);
            $this->assertCount(1, $otherUa);
            $this->manager->moveItinerary($tripsegId, $otherUa->first());
        } catch (\Exception $e) {
            $catched = true;
        }
        $this->assertTrue($catched);

        // copy trip to family member
        $trip2 = $this->manager->moveItinerary($tripsegId, $ua3, true);
        $this->assertNotEquals($trip->getId(), $trip2->getId());
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip2->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => $ua3->getUseragentid(),
        ]);
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => null,
        ]);

        // rewrite trip, recopy
        $lastId = $trip2->getId();
        $trip2 = $this->manager->moveItinerary($tripsegId, $ua3, true);
        $this->assertNotEquals($trip2->getId(), $lastId);
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip2->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => $ua3->getUseragentid(),
        ]);
        $this->db->dontSeeInDatabase("Trip", [
            "TripID" => $lastId,
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => $ua3->getUseragentid(),
        ]);

        // move to ua, rewrite
        $lastId = $trip2->getId();
        $trip2 = $this->manager->moveItinerary($tripsegId, $ua3);
        $this->assertNotEquals($trip2->getId(), $lastId);
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => $ua3->getUseragentid(),
        ]);
        $this->db->dontSeeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => null,
        ]);

        // backward, move to my
        $this->manager->moveItinerary($tripsegId, null);
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => null,
        ]);
        $this->db->dontSeeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => $ua3->getUseragentid(),
        ]);

        // move to other user
        $this->manager->moveItinerary($tripsegId, $ua1);
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $newUser->getUserid(),
            "UserAgentID" => null,
        ]);
        $this->db->dontSeeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $this->user->getUserid(),
            "UserAgentID" => null,
        ]);

        $this->container->get("aw.manager.user_manager")->loadToken($newUser, false);

        // copy to business
        /** @var Trip $trip2 */
        $trip2 = $this->manager->moveItinerary($tripsegId, $bua2, true);
        $this->assertNotEquals($trip->getId(), $trip2->getId());
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip2->getId(),
            "UserID" => $newBusinessUser->getUserid(),
            "UserAgentID" => null,
        ]);
        $this->db->seeInDatabase("Trip", [
            "TripID" => $trip->getId(),
            "UserID" => $newUser->getUserid(),
            "UserAgentID" => null,
        ]);
        $this->assertEquals(count($trip->getSegments()), count($trip2->getSegments()));

        // reservation
        $account = $this->getAccount('future.reservation', "");
        $account->setUserid($newUser);
        $this->em->persist($account);
        $this->em->flush();
        $this->aw->checkAccount($account->getAccountid(), true);
        $reservation = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class)
            ->findOneBy(["account" => $account]);

        // copy to business
        $reservation2 = $this->manager->moveItinerary("CI." . $reservation->getId(), $bua2, true);
        $this->assertNotEquals($reservation->getId(), $reservation2->getId());
        $this->db->seeInDatabase("Reservation", [
            "ReservationID" => $reservation2->getId(),
            "UserID" => $newBusinessUser->getUserid(),
            "UserAgentID" => null,
        ]);
        $this->db->seeInDatabase("Reservation", [
            "ReservationID" => $reservation->getId(),
            "UserID" => $newUser->getUserid(),
            "UserAgentID" => null,
        ]);
    }

    public function _after()
    {
        $this->manager = null;

        parent::_after(); // TODO: Change the autogenerated stub
    }

    private function getAccount($login, $pass)
    {
        $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find(Aw::TEST_PROVIDER_ID);
        $user = $this->user;
        $account = $this->container->get(AccountFactory::class)->create();
        $account->localPasswordManager = $this->container->get('aw.manager.local_passwords_manager');
        $account->setProviderid($provider);
        $account->setUserid($user);
        $account->setSavepassword(SAVE_PASSWORD_DATABASE);
        $account->setLogin($login);
        $account->setPass($pass);

        return $account;
    }
}
