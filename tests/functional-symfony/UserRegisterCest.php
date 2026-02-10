<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAwUsAccountList;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAwUsMailbox;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAwUsTimeline;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer;
use AwardWallet\MainBundle\Service\UsGreeting\EmailTask;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use Codeception\Stub\Expected;

/**
 * @group frontend-functional
 */
class UserRegisterCest
{
    use AutoVerifyMocksTrait;

    public function registerUserFromUs(\TestSymfonyGuy $I)
    {
        /** @var EmailTask[] $tasks */
        $tasks = [];

        $I->mockService(GeoLocation::class, $I->stubMakeEmpty(GeoLocation::class, [
            'getCountryIdByIp' => Country::UNITED_STATES,
        ]));
        $I->mockService(Producer::class, $I->stubMake(Producer::class, [
            'publish' => Expected::exactly(3, function ($task) use (&$tasks) {
                $tasks[] = $task;

                return null;
            }),
        ]));
        $now = date_create('now', new \DateTimeZone('America/New_York'));
        $this->registerUser($I);

        $I->assertEquals(3, count($tasks));

        // task 1
        /** @var EmailTask $newTask */
        $task = $tasks[0];
        $I->assertEquals(WelcomeToAwUsAccountList::class, $task->getEmailClass());
        $I->assertTrue($task->getSkipDoNotSend());

        // task 2
        /** @var EmailTask $newTask */
        $task = $tasks[1];
        $I->assertEquals(WelcomeToAwUsTimeline::class, $task->getEmailClass());
        $I->assertFalse($task->getSkipDoNotSend());

        // task 3
        /** @var EmailTask $newTask */
        $task = $tasks[2];
        $I->assertEquals(WelcomeToAwUsMailbox::class, $task->getEmailClass());
        $I->assertFalse($task->getSkipDoNotSend());
    }

    public function registerUserFromNonUs(\TestSymfonyGuy $I)
    {
        $I->mockService(GeoLocation::class, $I->stubMakeEmpty(GeoLocation::class, [
            'getCountryIdByIp' => Country::RUSSIA,
        ]));
        $I->mockService(Producer::class, $I->stubMake(Producer::class, [
            'publish' => Expected::exactly(0, function () {
                return null;
            }),
        ]));
        $this->registerUser($I);
    }

    private function registerUser(\TestSymfonyGuy $I)
    {
        $I->amOnRoute('aw_register');
        $I->saveCsrfToken();
        $randomName = StringHandler::getRandomName();
        $email = sprintf(
            '%s@%s.com',
            StringHandler::getRandomCode(5) . time(),
            StringHandler::getRandomCode(5) . '.mail'
        );
        $I->sendPost('/user/register', [
            'coupon' => '',
            'user' => [
                'email' => $email,
                'firstname' => $randomName['FirstName'],
                'lastname' => $randomName['LastName'],
                'pass' => 'Testpass123',
            ],
        ]);
    }

    private function getSendTime(\DateTime $now): \DateTime
    {
        $sendDateTime = (clone $now)->modify('+1 day')->setTime(9, 0, 0);

        if ($sendDateTime->getTimestamp() - $now->getTimestamp() < 24 * 60 * 60) {
            $sendDateTime->modify('+1 day');
        }

        return $sendDateTime;
    }
}
