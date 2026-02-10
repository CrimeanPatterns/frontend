<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\CouponAccessScenario;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class CouponControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function testAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var CouponAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $I->amOnRoute("aw_coupon_edit", ["couponId" => $scenario->couponId]);

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseCodeIs(200);
                $I->seeInSource($scenario->couponValue);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl("/login?BackTo=");
                $I->dontSeeInSource($scenario->couponValue);

                break;

            default:
                $I->seeResponseCodeIs(403);
                $I->dontSeeInSource($scenario->couponValue);
        }
    }

    /**
     * @dataProvider testAttachToAccountProvider
     */
    public function testAttachToAccountField(\TestSymfonyGuy $I, Example $example)
    {
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $agentRepository = $I->grabService('doctrine')->getManager()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $connectionManager = $I->grabService('aw.manager.connection_manager');

        $myId = $I->createAwUser(null, null, ['FirstName' => 'Tester']);
        /** @var Usr $me */
        $me = $userRepository->find($myId);
        $connectedUserId = $I->createAwUser(null, null, ['FirstName' => 'Connected']);
        $connectionId = $I->createConnection($connectedUserId, $myId, null, null, ['AccessLevel' => Useragent::ACCESS_WRITE]);
        $connectionManager->connectUser($userRepository->find($myId), $userRepository->find($connectedUserId));

        $agent = $agentRepository->find($connectionId);
        $client = $agentRepository->findOneBy(['clientid' => $agent->getAgentid()->getUserid()]);
        $connectionManager->approveConnection($client, $userRepository->find($connectedUserId));

        $I->createAwAccount($myId, 'aeroplan', 'MY_AC_ACCOUNT_1');
        $I->createAwAccount($myId, 'aeroplan', 'MY_AC_ACCOUNT_2', null, ['Balance' => '123456']);
        $I->createAwAccount($myId, 'goldpassport', 'MY_HT_ACCOUNT_1');
        $I->createAwAccount($connectedUserId, 'aeroplan', 'CONNECTED_AC_ACCOUNT_1');
        $I->createAwAccount($connectedUserId, 'aeroplan', 'CONNECTED_AC_ACCOUNT_2');

        $I->amOnPage("/coupon/add?_switch_user={$me->getLogin()}");
        $I->seeResponseCodeIs(200);
        $I->selectOption('providercoupon[owner]', $example['Owner']);
        $I->fillField('providercoupon[programname]', $example['Company']);
        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);

        // Have no method to verify options list, so just try to select them all
        foreach ($example['Options'] as $option) {
            $I->selectOption('providercoupon[account]', $option);
        }
    }

    public function testAttachToAccountProvider()
    {
        return [
            [
                'Owner' => 'Tester Petrovich',
                'Company' => 'Air Canada Aeroplan (Altitude)',
                'Options' => [
                    'Standalone',
                    'MY_AC_ACCOUNT_1',
                    'MY_AC_ACCOUNT_2 (123,456)',
                ],
            ],
            [
                'Owner' => 'Tester Petrovich',
                'Company' => 'Hyatt (World of Hyatt)',
                'Options' => [
                    'Standalone',
                    'MY_HT_ACCOUNT_1',
                ],
            ],
            [
                'Owner' => 'Connected Petrovich',
                'Company' => 'Air Canada Aeroplan (Altitude)',
                'Options' => [
                    'Standalone',
                    'CONNECTED_AC_ACCOUNT_1',
                ],
            ],
        ];
    }

    private function dataProvider()
    {
        return CouponAccessScenario::dataProvider();
    }
}
