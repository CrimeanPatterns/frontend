<?php

namespace AwardWallet\Tests\Unit\MainBundle\Command;

use AwardWallet\MainBundle\Command\SendEmailTemplateCommand;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\DataAllUsers;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\UsersWithAt201Subscription;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\UsersWithEarlySupporterDiscountSubscription;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\UsersWithFull30BucksSubscription;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProviderLoader;
use AwardWallet\MainBundle\Service\User\StateNotification;
use AwardWallet\Tests\Modules\DbBuilder\Cart;
use AwardWallet\Tests\Modules\DbBuilder\CartItem;
use AwardWallet\Tests\Unit\CommandTester;
use Clock\ClockNative;
use Codeception\Module\Aw;
use Codeception\Module\Mail;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @group frontend-unit
 * @extends CommandTester<SendEmailTemplateCommand>
 */
class SendEmailTemplateCommandTest extends CommandTester
{
    protected ?Mail $mail;

    public function _before()
    {
        $this->mail = $this->getModule('Mail');
        $this->mail->setSource(Mail::SOURCE_SWIFT);
        parent::_before();
    }

    public function _after()
    {
        parent::_after();

        $this->mail = null;
        $this->command = null;
    }

    public function testDryRunMode()
    {
        $code = $this->makeDataAllUsersEmailTemplate('data_all_users');
        $appBot = $this->makeAppBotDryRunMode();
        $emailLog = $this->makeEmailLogDryRunMode();

        $this->runCommand(
            $this->makeSendEmailTemplateCommand(
                $appBot->reveal(),
                $emailLog->reveal()
            ),
            [
                'code' => $code,
                '--userId' => [$this->user->getId()],
                '--dry-run' => true,
            ],
        );
        $this->mail->dontSeeEmailTo($this->user->getEmail());
    }

    /**
     * @dataProvider dataProviderForEarlySupporterSubscription
     * @param callable(int): Cart $makeCart
     */
    public function testAwPlusEarlySupporterSubscription(callable $makeCart)
    {
        $userId = $this->aw->createAwUser(
            $login = 'test' . $this->aw->grabRandomString(5),
            Aw::DEFAULT_PASSWORD,
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'Email' => $email = "{$login}@testmail.com",
            ],
        );
        $this->dbBuilder->makeCart($makeCart($userId));
        $code = $this->makeDataAllUsersEmailTemplate(UsersWithEarlySupporterDiscountSubscription::class);
        $appBot = $this->makeAppBotNormalMode();
        $emailLog = $this->makeEmailLogNormalMode();

        $this->runCommand(
            $this->makeSendEmailTemplateCommand($appBot->reveal(), $emailLog->reveal()),
            [
                'code' => $code,
                '--userId' => [$userId, $this->user->getId()],
            ],
        );
        $this->mail->seeEmailTo($email, UsersWithEarlySupporterDiscountSubscription::class, UsersWithEarlySupporterDiscountSubscription::class);
        $this->mail->dontSeeEmailTo($this->user->getEmail());
    }

    public function testAwPlusFull30BucksSubscription()
    {
        $userId = $this->aw->createAwUser(
            $login = 'test' . $this->aw->grabRandomString(5),
            Aw::DEFAULT_PASSWORD,
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'Email' => $email = "{$login}@testmail.com",
            ],
        );
        $this->dbBuilder->makeCart(new Cart(
            [new CartItem('Sub', AwPlusSubscription::TYPE, 50)],
            [
                'UserID' => $userId,
                'PaymentType' => PAYMENTTYPE_PAYPAL,
            ]
        ));
        $code = $this->makeDataAllUsersEmailTemplate(UsersWithFull30BucksSubscription::class);
        $appBot = $this->makeAppBotNormalMode();
        $emailLog = $this->makeEmailLogNormalMode();

        $this->runCommand(
            $this->makeSendEmailTemplateCommand($appBot->reveal(), $emailLog->reveal()),
            [
                'code' => $code,
                '--userId' => [$userId, $this->user->getId()],
            ],
        );
        $this->mail->seeEmailTo($email, UsersWithFull30BucksSubscription::class, UsersWithFull30BucksSubscription::class);
        $this->mail->dontSeeEmailTo($this->user->getEmail());
    }

    /**
     * @dataProvider dataProviderForTestAt201Subscription
     */
    public function testAt201Subscription(int $typeId)
    {
        $userId = $this->aw->createAwUser(
            $login = 'test' . $this->aw->grabRandomString(5),
            Aw::DEFAULT_PASSWORD,
            [
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'Email' => $email = "{$login}@testmail.com",
            ],
        );
        $this->dbBuilder->makeCart(new Cart(
            [new CartItem('Sub', $typeId, 10)],
            [
                'UserID' => $userId,
                'PaymentType' => PAYMENTTYPE_PAYPAL,
            ]
        ));
        $code = $this->makeDataAllUsersEmailTemplate(UsersWithAt201Subscription::class);
        $appBot = $this->makeAppBotNormalMode();
        $emailLog = $this->makeEmailLogNormalMode();

        $this->runCommand(
            $this->makeSendEmailTemplateCommand($appBot->reveal(), $emailLog->reveal()),
            [
                'code' => $code,
                '--userId' => [$userId, $this->user->getId()],
            ],
        );
        $this->mail->seeEmailTo($email, UsersWithAt201Subscription::class, UsersWithAt201Subscription::class);
        $this->mail->dontSeeEmailTo($this->user->getEmail());
    }

    public function testNormalMode()
    {
        $code = $this->makeDataAllUsersEmailTemplate(DataAllUsers::class);
        $appBot = $this->makeAppBotNormalMode();
        $emailLog = $this->makeEmailLogNormalMode();

        $this->runCommand(
            $this->makeSendEmailTemplateCommand($appBot->reveal(), $emailLog->reveal()),
            [
                'code' => $code,
                '--userId' => [$this->user->getId()],
            ],
        );
        $this->mail->seeEmailTo($this->user->getEmail(), DataAllUsers::class, DataAllUsers::class);
    }

    public function dataProviderForTestAt201Subscription()
    {
        return [
            [AT201Subscription1Month::TYPE],
            [AT201Subscription6Months::TYPE],
            [AT201Subscription1Year::TYPE],
        ];
    }

    public function dataProviderForEarlySupporterSubscription()
    {
        return [
            [fn (int $userId) => new Cart(
                [new CartItem('Sub', AwPlus::TYPE, 5)],
                [
                    'UserID' => $userId,
                    'PaymentType' => PAYMENTTYPE_PAYPAL,
                ]
            )],
        ];
    }

    protected function loginUser(int $userId): void
    {
        $this->assertNotNull($this->user = $this->em->getRepository(Usr::class)->find($userId));
    }

    /**
     * @param class-string<AbstractDataProvider> $dataProvider
     */
    private function makeDataAllUsersEmailTemplate(string $dataProvider): string
    {
        $this->db->haveInDatabase('EmailTemplate', [
            'Code' => $code = 'normal_mode_' . StringHandler::getRandomCode(10),
            'DataProvider' => DataProviderLoader::getCodeByClass($dataProvider),
            'Subject' => $dataProvider,
            'Body' => $dataProvider,
            'Layout' => 'blank_with_unsubscribe',
            'CreateDate' => \date('Y-m-d H:i:s'),
            'UpdateDate' => \date('Y-m-d H:i:s'),
            'Enabled' => true,
        ]);

        return $code;
    }

    private function makeSendEmailTemplateCommand(AppBot $appBot, EmailLog $emailLog): SendEmailTemplateCommand
    {
        return new SendEmailTemplateCommand(
            $appBot,
            $this->container->get('doctrine.orm.entity_manager'),
            $emailLog,
            $this->container->get(DataProviderLoader::class),
            [],
            $this->container->get(MailerCollection::class),
            $this->container->get(StateNotification::class),
            $this->container->get(LocalizeService::class),
            new ClockNative(),
        );
    }

    private function runCommand(SendEmailTemplateCommand $command, array $args): void
    {
        $this->cleanCommand();
        $this->command = $command;
        $this->initCommand($this->command);
        $this->clearLogs();
        $this->executeCommand($args);
    }

    private function makeAppBotNormalMode(): ObjectProphecy
    {
        $appBot = $this->prophesize(AppBot::class);
        $appBot
            ->send(
                Argument::any(),
                Argument::containingString('started sending'),
                Argument::cetera()
            )
            ->will(function () use ($appBot) {
                $appBot
                    ->send(
                        Argument::any(),
                        Argument::containingString('finished sending'),
                        Argument::cetera()
                    )
                    ->shouldBeCalledOnce();
            })
            ->shouldBeCalledOnce();

        return $appBot;
    }

    private function makeEmailLogNormalMode(): ObjectProphecy
    {
        $emailLog = $this->prophesize(EmailLog::class);
        $emailLog
            ->recordEmailToLog(Argument::cetera())
            ->shouldBeCalledOnce();

        return $emailLog;
    }

    private function makeAppBotDryRunMode(): ObjectProphecy
    {
        $appBot = $this->prophesize(AppBot::class);
        $appBot
            ->send(Argument::cetera())
            ->shouldNotBeCalled();

        return $appBot;
    }

    private function makeEmailLogDryRunMode(): ObjectProphecy
    {
        $emailLog = $this->prophesize(EmailLog::class);
        $emailLog
            ->recordEmailToLog(Argument::cetera())
            ->shouldNotBeCalled();

        return $emailLog;
    }
}
