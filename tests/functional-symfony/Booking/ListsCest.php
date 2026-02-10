<?php

namespace Booking;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Codeception\Scenario;

/**
 * @group booking
 * @group frontend-functional
 */
class ListsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testMarkReadAndUserList(\TestSymfonyGuy $I)
    {
        $user = $I->createAwUser(
            $username = \CommonUser::$user_username . $I->grabRandomString(5),
            $password = \CommonUser::$user_password
        );

        $request = 0;

        for ($i = 0; $i <= 3; $i++) {
            $request = $I->createAbRequest([
                'UserID' => $user,
            ]);
        }

        $page = $I->grabService('router')->generate('aw_booking_list_requests', ['_switch_user' => $username]);
        $I->amOnPage($page);

        $I->see('Active requests');
        $I->seeNumberOfElements(['css' => '.old-icon-booking-read-message'], 4);

        $I->saveCsrfToken();
        $I->sendAjaxGetRequest($I->grabService('router')->generate('aw_booking_view_markread', ['id' => $request, 'readed' => 'false']));
        codecept_debug($I->grabResponse());

        $I->amOnPage($I->grabService('router')->generate('aw_booking_list_requests'));
        $I->seeNumberOfElements(['css' => '.old-icon-booking-read-message'], 3);
    }

    public function testBookerList(\TestSymfonyGuy $I)
    {
        $request = $this->createRequestAsUserAndLoginAsBooker($I);
        $I->seeElement("//td[contains(@class, \"booking-id\")]//p[contains(text(), \"$request\")]");
    }

    public function testQueueFilter(\TestSymfonyGuy $I)
    {
        $request = $this->createRequestAsUserAndLoginAsBooker($I);
        $page = $this->getFullRoute($I, 'business_host', 'aw_booking_list_queue') . '?id_filter=' . $request;
        $I->amOnPage($page);
        $I->seeNumberOfElements(['css' => '.tablebody'], 1);
    }

    public function testSortQueue(\TestSymfonyGuy $I, Scenario $scenario)
    {
        /** @var UsrRepository $usrRep */
        $usrRep = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $bookerId = $I->createAwBookerStaff(
            $bookername = \CommonUser::$booker_login . $I->grabRandomString(5),
            \CommonUser::$booker_password
        );

        /** @var Usr $booker */
        $booker = $usrRep->find($bookerId);

        $user = $I->createAwUser(
            \CommonUser::$user_username . $I->grabRandomString(5),
            \CommonUser::$user_password
        );

        $requests = [];

        for ($i = 0; $i <= 3; $i++) {
            $requests[] = $I->createAbRequest([
                'UserID' => $user,
                'BookerUserID' => $usrRep->getBusinessByUser($booker)->getUserid(),
            ]);
        }

        $page = $this->getFullRoute($I, 'business_host', 'aw_booking_list_queue') . '?_switch_user=' . $bookername . "&sort=id&direction=desc";
        $I->amOnPage($page);
        $I->see('Booking requests');

        $I->seeNumberOfElements(['css' => '.tablebody'], 4);

        foreach ($requests as $key => $id) {
            $i = (3 - $key) + 1;
            $el = $I->grabTextFrom("(//td[@class = \"booking-id\"]/a/p)[{$i}]");
            $I->assertEquals($id, $el);
        }

        $page = $this->getFullRoute($I, 'business_host', 'aw_booking_list_queue') . '?sort=id&direction=desc';
        $I->amOnPage($page);

        foreach ($requests as $key => $id) {
            $i = (3 - $key) + 1;
            $el = $I->grabTextFrom("(//td[@class = \"booking-id\"]/a/p)[{$i}]");
            $I->assertEquals($id, $el);
        }

        $page = $this->getFullRoute($I, 'business_host', 'aw_booking_list_queue') . '?sort=id&direction=asc';
        $I->amOnPage($page);

        foreach ($requests as $key => $id) {
            $i = $key + 1;
            $el = $I->grabTextFrom("(//td[@class = \"booking-id\"]/a/p)[{$i}]");
            $I->assertEquals($id, $el);
        }
    }

    private function createRequestAsUserAndLoginAsBooker(\TestSymfonyGuy $I)
    {
        $user = $I->createAwUser(
            $username = \CommonUser::$user_username . $I->grabRandomString(5),
            $password = \CommonUser::$user_password
        );

        $request = $I->createAbRequest([
            'UserID' => $user,
            'BookerUserID' => \CommonUser::$booker_id,
        ]);
        $page = $I->grabService('router')->generate('aw_booking_list_requests', ['_switch_user' => $username]);
        $I->amOnPage($page);

        $I->see('Active requests');
        $I->resetCookie("MOCKSESSID");

        $page = $this->getFullRoute($I, 'business_host', 'aw_booking_list_queue') . '?_switch_user=' . \CommonUser::$booker_login;
        $I->amOnPage($page);
        $I->see('Booking requests');

        return $request;
    }

    private function getFullRoute(\TestSymfonyGuy $I, $hostParam, $route, $routeParams = [])
    {
        $container = $I->grabService('service_container');

        return
            $container->getParameter('requires_channel') .
            '://' .
            $container->getParameter($hostParam) .
            $container->get('router')->generate($route, $routeParams);
    }
}
