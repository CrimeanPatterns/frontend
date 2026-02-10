<?php

namespace Booking;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

class AddRequestAsBookerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EntityManager
     */
    protected $em;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService("router");

        /** @var EntityManager $em */
        $this->em = $I->grabService("doctrine")->getManager();

        $I->amOnBusiness();
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $this->em = null;
    }

    public function testAddRequest(\TestSymfonyGuy $I)
    {
        $I->resetLocker("booking_add", $I->getClientIp());
        $userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $booker = $userRep->find($I->createAwBookerStaff(
            $bookername = 'booker_' . $I->grabRandomString(5),
            $password = 'Password123456',
            [],
            true
        ));

        $user = $userRep->find($I->createAwUser(
            $username = 'user_' . $I->grabRandomString(5),
            $password = 'Password123456',
            [
                'FirstName' => 'Billy',
                'LastName' => 'Villy',
            ],
            null,
            true
        ));

        $businessId = $userRep->getBusinessByUser($booker)->getUserid();
        $accountId = $I->createAwAccount($user->getUserid(), 'delta', $username, $password);
        $I->shareAwAccount($accountId, $businessId);
        $I->connectUserWithBusiness($user->getUserid(), $businessId, UseragentRepository::ACCESS_WRITE);

        $I->amOnRoute("aw_booking_add_index", ["_switch_user" => $bookername]);
        $I->see("Public link to create new booking requests");
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('POST', $this->router->generate("aw_booking_add_index"), self::getFakeAbRequestData($user));
        $I->seeResponseCodeIs(200);
        $I->see("Booking Request #");
    }

    public static function getFakeAbRequestData(Usr $user)
    {
        $data = [
            "booking_request" => [
                "ContactName" => "Billy Villy",
                "ContactPhone" => "123-456",
                "ContactEmail" => "addrequest@gmail.com",
                "User" => $user->getUserid(),
                "Passengers" => [
                    [
                        "new_member" => 1,
                        "FirstName" => "John",
                        "LastName" => "Popov",
                        "Birthday" => "1980-06-01",
                        "Gender" => "M",
                        "Nationality" => ["choice" => "US"],
                    ],
                    [
                        "new_member" => 1,
                        "FirstName" => "Vassily",
                        "LastName" => "Charlot",
                        "Birthday" => "1986-10-13",
                        "Gender" => "M",
                        "Nationality" => ["choice" => "US"],
                    ],
                ],
                "Segments" => [
                    [
                        "RoundTrip" => 2,
                        "Dep" => "Zombilend",
                        "Arr" => "Perm",
                        "DepDateIdeal" => date("Y-m-d", strtotime("+2 days")),
                        "ReturnDateIdeal" => date("Y-m-d", strtotime("+10 days")),
                        "RoundTripDaysIdeal" => 6,
                    ],
                ],
            ],
        ];

        return $data;
    }
}
