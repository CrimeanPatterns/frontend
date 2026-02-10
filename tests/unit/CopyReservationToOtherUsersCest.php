<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Usr;

class CopyReservationToOtherUsersCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function copyFromFamilyMember(\TestSymfonyGuy $I)
    {
        /** @var Usr $user */
        $user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, [
            'FirstName' => 'John',
            'LastName' => 'Doe',
        ]));
        $familyMemberId = $I->createFamilyMember($user->getUserid(), 'Samantha', 'Doe');
        $providerName = 'p' . $I->grabRandomString(9);
        $itineraries = $this->getItineraries();
        $stage = null;
        $providerId = $I->createAwProvider(
            $providerName,
            $providerName,
            [],
            [
                'ParseItineraries' => function () use ($itineraries, &$stage) {
                    return $itineraries;
                },
            ]
        );
        $accountId = $I->createAwAccount($user->getUserid(), $providerId, 'login', null);
        $familyMemberAccountId = $I->createAwAccount($user->getUserid(), $providerId, 'login', null, ['UserAgentId' => $familyMemberId]);
        $I->sendAjaxGetRequest('/m/api/login_status?_switch_user=' . $user->getLogin());
        $I->checkAccount($familyMemberAccountId);
        $this->assertCount($I, $user->getUserid(), $familyMemberId);
        $I->checkAccount($accountId);
        $this->assertCount($I, $user->getUserid(), $familyMemberId);
        $I->checkAccount($familyMemberAccountId);
        $this->assertCount($I, $user->getUserid(), $familyMemberId);
    }

    private function getItineraries()
    {
        return [
            [
                'Kind' => 'T',
                'RecordLocator' => 'FLIGHT1',
                'TripSegments' => [
                    [
                        'AirlineName' => 'Air Transat',
                        'FlightNumber' => '10051',
                        'DepCode' => 'LAX',
                        'DepName' => 'LAX',
                        'DepDate' => (new \DateTime('yesterday 12:00'))->getTimestamp(),
                        'ArrCode' => 'JFK',
                        'ArrName' => 'JFK',
                        'ArrDate' => (new \DateTime('yesterday 13:00'))->getTimestamp(),
                    ],
                ],
                'Passengers' => ['John Doe', 'Samantha Doe'],
            ],
        ];
    }

    private function assertCount(\TestSymfonyGuy $I, int $userId, int $familyMemberId)
    {
        $userTripsCount = $I->grabCountFromDatabase('Trip', [
            'RecordLocator' => 'FLIGHT1',
            'Hidden' => 0,
            'UserID' => $userId,
            'UserAgentID' => null,
        ]);
        $I->assertSame('1', $userTripsCount);
        $familyMemberTripsCount = $I->grabCountFromDatabase('Trip', [
            'RecordLocator' => 'FLIGHT1',
            'Hidden' => 0,
            'UserID' => $userId,
            'UserAgentID' => $familyMemberId,
        ]);
        $I->assertSame('1', $familyMemberTripsCount);
    }
}
