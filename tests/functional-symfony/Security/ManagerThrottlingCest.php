<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\ManagerThrottlerListener\ManagerLocker;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ManagerThrottlerListener\ManagerThrottlerListener;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Clock\ClockTest;
use Codeception\Example;
use Prophecy\Argument;

/**
 * @group frontend-functional
 * @group security
 * @group manager
 */
class ManagerThrottlingCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const IMPERSONATE_ROUTE = '/manager/impersonate';
    private const ITINERARY_CHECK_ERROR_ROUTE = '/manager/itineraryCheckError';

    /**
     * @dataProvider testLockerDataProvider
     */
    public function testLocker(\TestSymfonyGuy $I, Example $example)
    {
        $I->createAwUser(
            $username = 'test' . $I->grabRandomString(),
            $I->grabRandomString(10),
            [],
            true
        );
        // change isThrottlingEnabled param to false (DEMO-MODE)
        $I->mockService(
            ManagerThrottlerListener::class,
            new ManagerThrottlerListener(
                $this->makeABLS($I, 'manager_lock_tier1_v1_', 5),
                $this->makeABLS($I, 'manager_lock_tier2_v2_', 10),
                $I->grabService("aw.security.antibruteforce.manager_tier3"),
                $I->grabService("aw.security.antibruteforce.manager_tier4"),
                (
                    $example['locked'] ?
                        $I->prophesize(AppBot::class)
                            ->send(
                                Slack::CHANNEL_AW_SYSADMIN,
                                Argument::allOf(
                                    Argument::containingString('Manager throttled'),
                                    (fn ($demoContains) => $example['throttling_enabled'] ?
                                        Argument::not($demoContains) :
                                        $demoContains
                                    )(Argument::containingString('DEMO-MODE'))
                                ),
                            ) :
                        $I->prophesize(AppBot::class)
                            ->send(Argument::cetera())
                            ->shouldNotBeCalled()
                )
                ->getObjectProphecy()
                ->reveal(),
                $I->grabService('logger'),
                $I->grabService("aw.security.token_storage"),
                $I->grabService("security.authorization_checker"),
                new ClockTest(),
                $I->grabService(ManagerLocker::class),
                $example['throttling_enabled']
            )
        );
        $I->amOnPage("/m/api/login_status?_switch_user=" . $username);

        foreach (\range(1, $example['requests_count']) as $_) {
            $I->amOnPage($example['route']);
        }

        if ($example['locked']) {
            if ($example['throttling_enabled']) {
                $I->seeResponseCodeIs(400);
                $I->seeResponseContains('Your account was throttled.');
                $I->amOnPage('/account/list/');
                $I->seeResponseCodeIs(400);
                $I->seeResponseContains('Your account was throttled.');
            } else {
                $I->seeResponseCodeIs(200);
                $I->dontSeeResponseContains('Your account was throttled.');
                $I->amOnPage('/account/list/');
                $I->seeResponseCodeIs(200);
                $I->dontSeeResponseContains('Your account was throttled.');
            }
        } else {
            $I->seeResponseCodeIs(200);
        }
    }

    private function testLockerDataProvider()
    {
        $examples = [];

        foreach ([true, false] as $throttlingEnabled) {
            $examples[] = ['requests_count' => 5,     'locked' => false, 'throttling_enabled' => $throttlingEnabled, 'route' => self::IMPERSONATE_ROUTE];
            $examples[] = ['requests_count' => 5 + 1, 'locked' => true, 'throttling_enabled' => $throttlingEnabled, 'route' => self::IMPERSONATE_ROUTE];
            $examples[] = ['requests_count' => 10, 'locked' => false, 'throttling_enabled' => $throttlingEnabled, 'route' => self::ITINERARY_CHECK_ERROR_ROUTE];
            $examples[] = ['requests_count' => 11, 'locked' => true, 'throttling_enabled' => $throttlingEnabled, 'route' => self::ITINERARY_CHECK_ERROR_ROUTE];
        }

        return $examples;
    }

    private function makeABLS(
        \TestSymfonyGuy $I,
        string $prefix,
        int $maxAttempts
    ) {
        return new AntiBruteforceLockerService(
            $I->grabService("aw.memcached"),
            $prefix,
            60,
            60,
            $maxAttempts,
            "Access denied",
            $I->grabService("monolog.logger.security")
        );
    }
}
