<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\AuthKeyChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\Tests\Unit\BaseTest;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\MakeLoggerMockTrait;
use AwardWallet\Tests\Unit\UpdateUserIdTrait;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\EntryPoint\AuthKeyChecker
 * @group security
 * @group frontend-unit
 */
class AuthKeyCheckerTest extends BaseTest
{
    use MakeLoggerMockTrait;
    use UpdateUserIdTrait;

    public function getCheckDataProvider()
    {
        $failLoggerCalls = [
            ['info', ["AuthKey cookie doesn't exist or more than a 10 years old", Argument::type('array')]],
        ];

        return [
            'success' => [
                '$cookieData' => ['u100500' => time() - 100],
                '$loggerCalls' => [
                    ['info', ['AuthKey cookie exists and less than a 10 years old', Argument::type('array')]],
                ],
                '$success' => true,
            ],
            'empty data' => [
                '$cookieData' => [],
                '$loggerCalls' => $failLoggerCalls,
                '$success' => false,
            ],
            'expired' => [
                '$cookieData' => ['u100500' => time() - DateTimeUtils::SECONDS_PER_DAY * 365 * 11],
                '$loggerCalls' => $failLoggerCalls,
                '$success' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCheckDataProvider
     */
    public function testCheck(array $cookieData, array $loggerCalls, bool $success)
    {
        $request = new Request([], [], [], [
            AuthKeyChecker::KEY_NAME => \base64_encode(
                @AESEncode(
                    \json_encode($cookieData),
                    $key = 'somekey'
                )
            ),
        ]);
        $credentials =
            (new Credentials(new StepData(), $request))
            ->setUser($this->updateUserId(new Usr(), 100500));

        /** @var AuthKeyChecker $checker */
        $checker = new AuthKeyChecker(
            $this->makeLoggerMock($loggerCalls),
            $key,
            'keyOld'
        );

        $this->assertEquals($success, $checker->keyExists($credentials));
    }

    public function testUserInSkippingGroup()
    {
        $credentials =
            (new Credentials(new StepData(), new Request()))
            ->setUser(
                (new Usr())
                ->addGroup(
                    (new Sitegroup())
                    ->setGroupname(AuthKeyChecker::SKIP_AUTHKEY_CHECK))
            );

        /** @var AuthKeyChecker $checker */
        $checker = new AuthKeyChecker(
            $this->makeLoggerMock([
                ['warning', [Argument::containingString("User in skipping group"), Argument::type('array')]],
            ]),
            'key',
            'keyOld'
        );

        $this->assertFalse($checker->keyExists($credentials));
    }

    public function testTestIpAddressCookie()
    {
        $request = new Request([], [], [], [
            AuthKeyChecker::TEST_IP_ADDRESS_COOKIE_NAME => '1',
        ]);
        $credentials =
            (new Credentials(new StepData(), $request))
            ->setUser(new Usr());

        /** @var AuthKeyChecker $checker */
        $checker = new AuthKeyChecker(
            $this->makeLoggerMock([
                ['warning', [Argument::containingString("cookie"), Argument::type('array')]],
            ]),
            'key',
            'keyOld'
        );

        $this->assertFalse($checker->keyExists($credentials));
    }
}
