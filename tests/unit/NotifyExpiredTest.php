<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Command\NotifyExpiredCommand;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Blog\BlogPostMock;
use AwardWallet\Tests\Modules\DbBuilder\Account as DbAccount;
use AwardWallet\Tests\Modules\DbBuilder\Provider as DBProvider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCoupon as DbProviderCoupon;
use AwardWallet\Tests\Modules\DbBuilder\SubAccount as DbSubAccount;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Modules\DbBuilder\User as DBUser;
use AwardWallet\Tests\Modules\DbBuilder\UserAgent as DBUserAgent;
use Codeception\Module\Aw;
use Codeception\Module\Mail;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class NotifyExpiredTest extends CommandTester
{
    private ?Mail $mail;

    public function _before()
    {
        parent::_before();

        $this->mockService('monolog.logger.stat', $this->container->get(LoggerInterface::class));
        $this->mockService(BlogPostMock::class, $this->makeEmpty(BlogPostMock::class, [
            'fetchPostById' => function ($postIds, $withoutCache) {
                return [
                    [
                        'title' => 'Emirates Drops Economy Award Pricing By Up to 55%, Decreases Mileage Earnings',
                        'imageURL' => 'https://awardwallet.com/blog/wp-content/uploads/2024/05/Featured-Hilton-Resort-Waikiki-Hawaii-nico-smit-unsplash-325x260.jpg',
                        'postURL' => 'http://site.com/',
                    ],
                ];
            },
        ]));
        $this->initCommand($this->container->get(NotifyExpiredCommand::class));
        /** @var Mail mail */
        $this->mail = $this->getModule('Mail');
    }

    public function _after()
    {
        $this->mail = null;
        $this->cleanCommand();
        parent::_after();
    }

    public function testNoSending()
    {
        $this->assertAfterRun([]);
    }

    /**
     * @dataProvider commonDataProvider
     * TODO: migrate other tests to this data provider
     */
    public function testCommon($data, array $expectedEmails = [], array $expectedLogs = [], array $notExpectedLogs = [])
    {
        if ($data instanceof DbAccount) {
            $this->dbBuilder->makeAccount($data);
            $userId = $data->getFields()['UserID'];
        } elseif ($data instanceof DbProviderCoupon) {
            $this->dbBuilder->makeProviderCoupon($data);
            $userId = $data->getFields()['UserID'];
        } elseif (is_array($data)) {
            $userId = null;

            foreach ($data as $entity) {
                if ($entity instanceof DbAccount) {
                    $this->dbBuilder->makeAccount($entity);
                    $userId = $entity->getFields()['UserID'];
                } elseif ($entity instanceof DbProviderCoupon) {
                    $this->dbBuilder->makeProviderCoupon($entity);
                    $userId = $entity->getFields()['UserID'];
                } elseif ($entity instanceof DBUserAgent) {
                    $this->dbBuilder->makeUserAgent($entity);
                }
            }

            if (is_null($userId)) {
                throw new \InvalidArgumentException('Account data must contain user');
            }
        } else {
            throw new \InvalidArgumentException('Invalid account data');
        }

        $this->executeCommand(array_merge(['--userId' => [$userId]]));
        $this->logContains(sprintf('filter by userId: [%d]', $userId));
        $this->logContains(sprintf('sent %d emails', count($expectedEmails)));
        $emailsCount = array_count_values($expectedEmails);

        foreach ($emailsCount as $expectedEmail => $count) {
            if ($count > 1) {
                $this->logContainsCount(sprintf('mailing to %s', $expectedEmail), $count);
            } else {
                $this->logContains(sprintf('mailing to %s', $expectedEmail));
            }
        }

        foreach ($expectedLogs as $expectedLog) {
            $this->logContains($expectedLog);
        }

        foreach ($notExpectedLogs as $notExpectedLog) {
            $this->logNotContains($notExpectedLog);
        }
    }

    public function commonDataProvider(): array
    {
        return [
            // account, user
            'account, user, success send' => [
                self::account($user = self::user(), [], self::provider()),
                [$user->getFields()['Email']],
            ],
            'account, user, Usr.EmailExpiration = 0' => [
                self::account(self::user(['EmailExpiration' => 0]), [], self::provider()),
            ],
            'account, user, Account.DontTrackExpiration = 1' => [
                self::account(self::user(), ['DontTrackExpiration' => 1], self::provider()),
            ],
            'account, user, Account.State disabled' => [
                self::account($user = self::user(), ['State' => ACCOUNT_DISABLED], self::provider()),
                [$user->getFields()['Email']],
            ],
            'account, user, Account.Balance = 0' => [
                self::account(self::user(), ['Balance' => 0], self::provider()),
            ],
            'account, user, 2 accounts' => [
                [
                    self::account($user = self::user(), ['Balance' => 1000], $provider = self::provider()),
                    self::account($user, ['Balance' => 500], $provider),
                ],
                [$user->getFields()['Email'], $user->getFields()['Email']],
                [
                    sprintf('[%s] mailing to', $user->getFields()['Email']),
                ],
            ],

            // custom account, user
            'custom account, user, success send' => [
                self::account($user = self::user(), ['ProgramName' => 'Custom']),
                [$user->getFields()['Email']],
            ],
            'custom account, user, Usr.EmailExpiration = 0' => [
                self::account(self::user(['EmailExpiration' => 0]), ['ProgramName' => 'Custom']),
            ],
            'custom account, user, Account.DontTrackExpiration = 1' => [
                self::account(self::user(), ['DontTrackExpiration' => 1, 'ProgramName' => 'Custom']),
            ],
            'custom account, user, Account.State disabled' => [
                self::account($user = self::user(), ['State' => ACCOUNT_DISABLED, 'ProgramName' => 'Custom']),
                [$user->getFields()['Email']],
            ],
            'custom account, user, Account.Balance = 0' => [
                self::account(self::user(), ['Balance' => 0, 'ProgramName' => 'Custom']),
            ],
            'custom account, user, 2 accounts' => [
                [
                    self::account($user = self::user(), ['Balance' => 1000, 'ProgramName' => 'Custom']),
                    self::account($user, ['Balance' => 500, 'ProgramName' => 'Custom']),
                ],
                [$user->getFields()['Email'], $user->getFields()['Email']],
                [
                    sprintf('[%s] mailing to', $user->getFields()['Email']),
                ],
            ],

            // account, family
            'account, family, success send' => [
                self::account($family = self::family(self::user()), [], self::provider()),
                [$family->getFields()['Email']],
                ['email to user agent, skipping'],
            ],
            'account, family, Usr.EmailFamilyMemberAlert = 1' => [
                self::account($family = self::family($user = self::user(['EmailFamilyMemberAlert' => 1])), [], self::provider()),
                [$family->getFields()['Email'], $user->getFields()['Email']],
            ],
            'account, family, UserAgent.Email = null' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 1]), ['Email' => null]), [], self::provider()),
                [$user->getFields()['Email']],
            ],
            'account, family, UserAgent.SendEmails = 0' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 1]), ['SendEmails' => 0]), [], self::provider()),
                [$user->getFields()['Email']],
            ],
            'account, family, Usr.Email = UserAgent.Email' => [
                self::account($family = self::family($user = self::user(['EmailFamilyMemberAlert' => 1]), ['Email' => $user->getFields()['Email']]), [], self::provider()),
                [$family->getFields()['Email']],
                ['email to user agent, skipping'],
            ],
            'account, family, Usr.EmailFamilyMemberAlert = 0, UserAgent.Email = null' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 0]), ['Email' => null]), [], self::provider()),
                [$user->getFields()['Email']],
            ],
            'account, family, Usr.EmailFamilyMemberAlert = 0, UserAgent.SendEmails = 0' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 0]), ['SendEmails' => 0]), [], self::provider()),
                [$user->getFields()['Email']],
            ],
            'account, family, Usr.EmailExpiration = 0' => [
                self::account($family = self::family(self::user(['EmailExpiration' => 0])), [], self::provider()),
                [],
            ],
            'account, family, Account.DontTrackExpiration = 1' => [
                self::account(self::family(self::user()), ['DontTrackExpiration' => 1], self::provider()),
            ],
            'account, family, Account.State disabled' => [
                self::account($family = self::family(self::user()), ['State' => ACCOUNT_DISABLED], self::provider()),
                [$family->getFields()['Email']],
            ],
            'account, family, Account.Balance = 0' => [
                self::account(self::family(self::user()), ['Balance' => 0], self::provider()),
            ],
            'account, family, 2 accounts' => [
                [
                    self::account($user = self::user(['EmailFamilyMemberAlert' => 1]), [], $provider = self::provider()),
                    self::account($family = self::family($user), ['Balance' => 500], $provider),
                ],
                [$user->getFields()['Email'], $user->getFields()['Email'], $family->getFields()['Email']],
                [
                    sprintf('[UA][Jessica Smith][%s] mailing to', $family->getFields()['Email']),
                    sprintf('[U][Jessica Smith][%s] mailing to', $user->getFields()['Email']),
                ],
            ],

            // custom account, family
            'custom account, family, success send' => [
                self::account($family = self::family(self::user()), ['ProgramName' => 'Custom']),
                [$family->getFields()['Email']],
                ['email to user agent, skipping'],
            ],
            'custom account, family, Usr.EmailFamilyMemberAlert = 1' => [
                self::account($family = self::family($user = self::user(['EmailFamilyMemberAlert' => 1])), ['ProgramName' => 'Custom']),
                [$family->getFields()['Email'], $user->getFields()['Email']],
            ],
            'custom account, family, UserAgent.Email = null' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 1]), ['Email' => null]), ['ProgramName' => 'Custom']),
                [$user->getFields()['Email']],
            ],
            'custom account, family, UserAgent.SendEmails = 0' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 1]), ['SendEmails' => 0]), ['ProgramName' => 'Custom']),
                [$user->getFields()['Email']],
            ],
            'custom account, family, Usr.Email = UserAgent.Email' => [
                self::account($family = self::family($user = self::user(['EmailFamilyMemberAlert' => 1]), ['Email' => $user->getFields()['Email']]), ['ProgramName' => 'Custom']),
                [$family->getFields()['Email']],
                ['email to user agent, skipping'],
            ],
            'custom account, family, Usr.EmailFamilyMemberAlert = 0, UserAgent.Email = null' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 0]), ['Email' => null]), ['ProgramName' => 'Custom']),
                [$user->getFields()['Email']],
            ],
            'custom account, family, Usr.EmailFamilyMemberAlert = 0, UserAgent.SendEmails = 0' => [
                self::account(self::family($user = self::user(['EmailFamilyMemberAlert' => 0]), ['SendEmails' => 0]), ['ProgramName' => 'Custom']),
                [$user->getFields()['Email']],
            ],
            'custom account, family, Usr.EmailExpiration = 0' => [
                self::account($family = self::family(self::user(['EmailExpiration' => 0])), ['ProgramName' => 'Custom']),
                [],
            ],
            'custom account, family, Account.DontTrackExpiration = 1' => [
                self::account(self::family(self::user()), ['DontTrackExpiration' => 1, 'ProgramName' => 'Custom']),
            ],
            'custom account, family, Account.State disabled' => [
                self::account($family = self::family(self::user()), ['State' => ACCOUNT_DISABLED, 'ProgramName' => 'Custom']),
                [$family->getFields()['Email']],
            ],
            'custom account, family, Account.Balance = 0' => [
                self::account(self::family(self::user()), ['Balance' => 0, 'ProgramName' => 'Custom']),
            ],
            'custom account, family, 2 accounts' => [
                [
                    self::account($user = self::user(['EmailFamilyMemberAlert' => 1]), ['ProgramName' => 'Custom']),
                    self::account($family = self::family($user), ['Balance' => 500, 'ProgramName' => 'Custom']),
                ],
                [$user->getFields()['Email'], $user->getFields()['Email'], $family->getFields()['Email']],
                [
                    sprintf('[UA][Jessica Smith][%s] mailing to', $family->getFields()['Email']),
                    sprintf('[U][Jessica Smith][%s] mailing to', $user->getFields()['Email']),
                ],
            ],

            // account, business family
            'account, business family, success send' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($family = self::family($business), [], self::provider()),
                ],
                [$family->getFields()['Email']],
            ],
            'account, business family, UserAgent.Email = null' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(['EmailFamilyMemberAlert' => 1]), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account(self::family($business, ['Email' => null]), [], self::provider()),
                ],
            ],
            'account, business family, UserAgent.SendEmails = 0' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(['EmailFamilyMemberAlert' => 1]), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account(self::family($business, ['SendEmails' => 0]), [], self::provider()),
                ],
            ],

            // custom account, business family
            'custom account, business family, success send' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($family = self::family($business), ['ProgramName' => 'Custom']),
                ],
                [$family->getFields()['Email']],
            ],
            'custom account, business family, UserAgent.Email = null' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(['EmailFamilyMemberAlert' => 1]), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account(self::family($business, ['Email' => null]), ['ProgramName' => 'Custom']),
                ],
            ],
            'custom account, business family, UserAgent.SendEmails = 0' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(['EmailFamilyMemberAlert' => 1]), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account(self::family($business, ['SendEmails' => 0]), ['ProgramName' => 'Custom']),
                ],
            ],
            'custom account, business family, 2 accounts' => [
                [
                    new DBUserAgent($admin = self::user(), $business = self::business(['EmailFamilyMemberAlert' => 1]), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($family = self::family($business), ['ProgramName' => 'Custom']),
                    self::account($family, ['Balance' => 500, 'ProgramName' => 'Custom']),
                ],
                [$family->getFields()['Email'], $family->getFields()['Email']],
            ],

            // account, business
            'account, business, success send' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, [], self::provider()),
                ],
                [$admin1->getFields()['Email'], $admin2->getFields()['Email']],
            ],
            'account, business, Usr.EmailExpiration = 0' => [
                [
                    new DBUserAgent($admin1 = self::user(['EmailExpiration' => 0]), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, [], self::provider()),
                ],
                [$admin2->getFields()['Email']],
            ],
            'account, business, Account.DontTrackExpiration = 1' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['DontTrackExpiration' => 1], self::provider()),
                ],
            ],
            'account, business, Account.State disabled' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['State' => ACCOUNT_DISABLED], self::provider()),
                ],
                [$admin1->getFields()['Email'], $admin2->getFields()['Email']],
            ],
            'account, business, Account.Balance = 0' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['Balance' => 0], self::provider()),
                ],
            ],
            'account, business, 2 accounts' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['Balance' => 1000], $provider = self::provider()),
                    self::account($business, ['Balance' => 500], $provider),
                ],
                [$admin1->getFields()['Email'], $admin1->getFields()['Email'], $admin2->getFields()['Email'], $admin2->getFields()['Email']],
            ],

            // custom account, business
            'custom account, business, success send' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['ProgramName' => 'Custom']),
                ],
                [$admin1->getFields()['Email'], $admin2->getFields()['Email']],
            ],
            'custom account, business, Usr.EmailExpiration = 0' => [
                [
                    new DBUserAgent($admin1 = self::user(['EmailExpiration' => 0]), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['ProgramName' => 'Custom']),
                ],
                [$admin2->getFields()['Email']],
            ],
            'custom account, business, Account.DontTrackExpiration = 1' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['DontTrackExpiration' => 1, 'ProgramName' => 'Custom']),
                ],
            ],
            'custom account, business, Account.State disabled' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['State' => ACCOUNT_DISABLED, 'ProgramName' => 'Custom']),
                ],
                [$admin1->getFields()['Email'], $admin2->getFields()['Email']],
            ],
            'custom account, business, Account.Balance = 0' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['Balance' => 0, 'ProgramName' => 'Custom']),
                ],
            ],
            'custom account, business, 2 accounts' => [
                [
                    new DBUserAgent($admin1 = self::user(), $business = self::business(), ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin1, ['AccessLevel' => ACCESS_WRITE]),
                    new DBUserAgent($admin2 = self::user(), $business, ['AccessLevel' => ACCESS_ADMIN]),
                    new DBUserAgent($business, $admin2, ['AccessLevel' => ACCESS_WRITE]),
                    self::account($business, ['Balance' => 1000, 'ProgramName' => 'Custom']),
                    self::account($business, ['Balance' => 500, 'ProgramName' => 'Custom']),
                ],
                [$admin1->getFields()['Email'], $admin1->getFields()['Email'], $admin2->getFields()['Email'], $admin2->getFields()['Email']],
            ],
        ];
    }

    public function testUserCoupon()
    {
        $coupon = $this->createCoupon();
        $this->assertAfterRun([$this->getUserEmail()]);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);

        // passport - dont send
        $this->db->updateInDatabase('ProviderCoupon', [
            'TypeID' => Providercoupon::TYPE_PASSPORT,
        ], ['ProviderCouponID' => $coupon->getProvidercouponid()]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
        $this->assertAfterRun([]);
    }

    public function testUaCoupon()
    {
        $familyMember = $this->createFamilyMember();
        $this->setEmailFamilyMemberAlert(false);

        $coupon = $this->createCoupon(null, $familyMember);
        $this->assertAfterRun([$familyMember->getEmail()]);

        $this->setEmailFamilyMemberAlert(true);
        $this->assertAfterRun([$this->getUserEmail(), $familyMember->getEmail()]);

        $this->setFamilyMemberEmail($familyMember, null, true);
        $this->assertAfterRun([$this->getUserEmail()]);

        $this->setFamilyMemberEmail($familyMember, 'test@test.com', false);
        $this->assertAfterRun([$this->getUserEmail()]);

        $this->setFamilyMemberEmail($familyMember, $this->getUserEmail(), true);
        $this->assertAfterRun([$familyMember->getEmail()]);

        $this->setFamilyMemberEmail($familyMember, null, true);
        $this->setEmailFamilyMemberAlert(false);
        $this->assertAfterRun([$this->getUserEmail()]);
        $this->setFamilyMemberEmail($familyMember, 'test@test.com', true);
        $this->setEmailFamilyMemberAlert(true);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);

        // passport - dont send
        $this->db->updateInDatabase('ProviderCoupon', [
            'TypeID' => Providercoupon::TYPE_PASSPORT,
        ], ['ProviderCouponID' => $coupon->getProvidercouponid()]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
        $this->assertAfterRun([]);
    }

    public function testUaBusinessCoupon()
    {
        [$business] = $this->createBusiness();
        $familyMember = $this->createFamilyMember($business);
        $coupon = $this->createCoupon($business, $familyMember);
        $this->assertAfterRun([$familyMember->getEmail()], $business);

        $this->setFamilyMemberEmail($familyMember, null);
        $this->assertAfterRun([], $business);

        $this->setFamilyMemberEmail($familyMember, 'test@test.com', false);
        $this->assertAfterRun([], $business);

        // passport - dont send
        $this->db->updateInDatabase('ProviderCoupon', [
            'TypeID' => Providercoupon::TYPE_PASSPORT,
        ], ['ProviderCouponID' => $coupon->getProvidercouponid()]);
        $this->setFamilyMemberEmail($familyMember, 'test@test.com', true);
        $this->assertAfterRun([], $business);
    }

    public function testBusinessCoupon()
    {
        /** @var Usr[] $admins */
        [$business, $admins] = $this->createBusiness([$this->user, null]);

        $coupon = $this->createCoupon($business);
        $this->assertAfterRun([$admins[0]->getEmail(), $admins[1]->getEmail()], $business);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([$admins[1]->getEmail()], $business);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);

        // passport - dont send
        $this->db->updateInDatabase('ProviderCoupon', [
            'TypeID' => Providercoupon::TYPE_PASSPORT,
        ], ['ProviderCouponID' => $coupon->getProvidercouponid()]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
        $this->assertAfterRun([]);
    }

    public function testUserSubAccount()
    {
        [$account, $subaccount] = $this->createAccountWithSubaccounts();
        $this->assertAfterRun([$this->getUserEmail()]);

        // DontTrackExpiration = 1
        $this->setDontTrackExpiration($account, true);
        $this->assertAfterRun([$this->getUserEmail()]);
        $this->setDontTrackExpiration($account, false);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);

        // IsHidden = 1
        $this->setSubaccountHidden($subaccount, true);
        $this->assertAfterRun([]);
        $this->setSubaccountHidden($subaccount, false);

        // DontSendEmailsSubaccExpDate = 1
        $this->setDontSendEmailsSubaccExpDate($account, true);
        $this->assertAfterRun([]);
        $this->setDontSendEmailsSubaccExpDate($account, false);

        // Balance = 0
        $this->setAccountBalance($subaccount, 0);
        $this->assertAfterRun([]);

        // Balance = null
        $this->setAccountBalance($subaccount, null);
        $this->assertAfterRun([$this->getUserEmail()]);
    }

    public function testUaSubAccount()
    {
        $familyMember = $this->createFamilyMember();
        $this->setEmailFamilyMemberAlert(false);

        [$account, $subaccount] = $this->createAccountWithSubaccounts(null, $familyMember);
        $this->assertAfterRun([$familyMember->getEmail()]);

        $this->setEmailFamilyMemberAlert(true);
        $this->assertAfterRun([$this->getUserEmail(), $familyMember->getEmail()]);

        $this->setFamilyMemberEmail($familyMember, null, true);
        $this->assertAfterRun([$this->getUserEmail()]);

        $this->setFamilyMemberEmail($familyMember, 'test@test.com', false);
        $this->assertAfterRun([$this->getUserEmail()]);

        $this->setFamilyMemberEmail($familyMember, $this->getUserEmail(), true);
        $this->assertAfterRun([$familyMember->getEmail()]);

        $this->setFamilyMemberEmail($familyMember, null, true);
        $this->setEmailFamilyMemberAlert(false);
        $this->assertAfterRun([$this->getUserEmail()]);
        $this->setFamilyMemberEmail($familyMember, 'test@test.com', true);
        $this->setEmailFamilyMemberAlert(true);

        // DontTrackExpiration = 1
        $this->setDontTrackExpiration($account, true);
        $this->assertAfterRun([$this->getUserEmail(), $familyMember->getEmail()]);
        $this->setDontTrackExpiration($account, false);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);

        // IsHidden = 1
        $this->setSubaccountHidden($subaccount, true);
        $this->assertAfterRun([]);
        $this->setSubaccountHidden($subaccount, false);

        // DontSendEmailsSubaccExpDate = 1
        $this->setDontSendEmailsSubaccExpDate($account, true);
        $this->assertAfterRun([]);
        $this->setDontSendEmailsSubaccExpDate($account, false);

        // Account state
        $this->setAccountState($account, ACCOUNT_DISABLED);
        $this->assertAfterRun([$this->getUserEmail(), $familyMember->getEmail()]);
        $this->setAccountState($account, ACCOUNT_ENABLED);

        // Balance = 0
        $this->setAccountBalance($subaccount, 0);
        $this->assertAfterRun([]);

        // Balance = null
        $this->setAccountBalance($subaccount, null);
        $this->assertAfterRun([$this->getUserEmail(), $familyMember->getEmail()]);
    }

    public function testUaBusinessSubAccount()
    {
        [$business] = $this->createBusiness();
        $familyMember = $this->createFamilyMember($business);
        [$account, $subaccount] = $this->createAccountWithSubaccounts($business, $familyMember);
        $this->assertAfterRun([$familyMember->getEmail()], $business);

        $this->setFamilyMemberEmail($familyMember, null);
        $this->assertAfterRun([], $business);

        $this->setFamilyMemberEmail($familyMember, 'test@test.com', false);
        $this->assertAfterRun([], $business);
    }

    public function testBusinessSubAccount()
    {
        /** @var Usr[] $admins */
        [$business, $admins] = $this->createBusiness([$this->user, null]);

        [$account, $subaccount] = $this->createAccountWithSubaccounts($business);
        $this->setDontSendEmailsSubaccExpDate($account, false);
        $this->assertAfterRun([$admins[0]->getEmail(), $admins[1]->getEmail()], $business);

        // DontTrackExpiration = 1
        $this->setDontTrackExpiration($account, true);
        $this->assertAfterRun([$admins[0]->getEmail(), $admins[1]->getEmail()], $business);
        $this->setDontTrackExpiration($account, false);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([$admins[1]->getEmail()], $business);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);

        // IsHidden = 1
        $this->setSubaccountHidden($subaccount, true);
        $this->assertAfterRun([]);
        $this->setSubaccountHidden($subaccount, false);

        // DontSendEmailsSubaccExpDate = 1
        $this->setDontSendEmailsSubaccExpDate($account, true);
        $this->assertAfterRun([], $business);
        $this->setDontSendEmailsSubaccExpDate($account, false);

        // Balance = 0
        $this->setAccountBalance($subaccount, 0);
        $this->assertAfterRun([], $business);

        // Balance = null
        $this->setAccountBalance($subaccount, null);
        $this->assertAfterRun([$admins[0]->getEmail(), $admins[1]->getEmail()], $business);
    }

    public function testPassportNoSending()
    {
        $coupon = $this->createCoupon(null, null, '+9 months - 1 day', true);
        $this->assertAfterRun([]);
        $this->db->updateInDatabase('ProviderCoupon', [
            'ExpirationDate' => date('Y-m-d', strtotime('+9 months + 1 day')),
        ], ['ProviderCouponID' => $coupon->getId()]);
        $this->assertAfterRun([]);
        $this->db->updateInDatabase('ProviderCoupon', [
            'ExpirationDate' => date('Y-m-d', strtotime('+9 months')),
        ], ['ProviderCouponID' => $coupon->getId()]);
        $this->assertAfterRun([$this->getUserEmail()]);
        $this->mail->seeEmailTo($this->getUserEmail(), 'Passport Expires in 9 Months');
    }

    public function testUserPassport()
    {
        $coupon = $this->createCoupon(null, null, '+9 months', true);
        $this->assertAfterRun([$this->getUserEmail()]);
        $this->mail->seeEmailTo($this->getUserEmail(), 'Passport Expires in 9 Months');

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
    }

    public function testUaPassport()
    {
        $familyMember = $this->createFamilyMember();
        $this->setEmailFamilyMemberAlert(false);

        $coupon = $this->createCoupon(null, $familyMember, '+9 months', true);
        $this->assertAfterRun([$familyMember->getEmail()]);
        $this->mail->seeEmailTo($familyMember->getEmail(), 'Passport Expires in 9 Months');

        $this->setEmailFamilyMemberAlert(true);
        $this->assertAfterRun([$this->getUserEmail(), $familyMember->getEmail()]);

        $this->setFamilyMemberEmail($familyMember, null, true);
        $this->assertAfterRun([$this->getUserEmail()]);

        $this->setFamilyMemberEmail($familyMember, 'test@test.com', false);
        $this->assertAfterRun([$this->getUserEmail()]);

        $this->setFamilyMemberEmail($familyMember, $this->getUserEmail(), true);
        $this->assertAfterRun([$familyMember->getEmail()]);

        $this->setFamilyMemberEmail($familyMember, null, true);
        $this->setEmailFamilyMemberAlert(false);
        $this->assertAfterRun([$this->getUserEmail()]);
        $this->setFamilyMemberEmail($familyMember, 'test@test.com', true);
        $this->setEmailFamilyMemberAlert(true);

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([]);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
    }

    public function testUaBusinessPassport()
    {
        [$business] = $this->createBusiness();
        $familyMember = $this->createFamilyMember($business);
        $coupon = $this->createCoupon($business, $familyMember, '+9 months', true);
        $this->assertAfterRun([$familyMember->getEmail()], $business);
        $this->mail->seeEmailTo($familyMember->getEmail(), 'Passport Expires in 9 Months');

        $this->setFamilyMemberEmail($familyMember, null);
        $this->assertAfterRun([], $business);

        $this->setFamilyMemberEmail($familyMember, 'test@test.com', false);
        $this->assertAfterRun([], $business);
    }

    public function testBusinessPassport()
    {
        /** @var Usr[] $admins */
        [$business, $admins] = $this->createBusiness([$this->user, null]);

        $coupon = $this->createCoupon($business, null, '+9 months', true);
        $this->assertAfterRun([$admins[0]->getEmail(), $admins[1]->getEmail()], $business);
        $this->mail->seeEmailTo($admins[0]->getEmail(), 'Passport Expires in 9 Months');
        $this->mail->seeEmailTo($admins[1]->getEmail(), 'Passport Expires in 9 Months');

        // EmailExpiration = 0
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_NEVER);
        $this->assertAfterRun([$admins[1]->getEmail()], $business);
        $this->setEmailExpiration(Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7);
    }

    public function testFutureSuccessCheckDate()
    {
        $account = $this->createAccount();
        $this->checkAccount($account->getAccountid());
        $account->setSuccesscheckdate(new \DateTime('+1 month'));
        $this->em->flush();
        $this->assertAfterRun([]);
    }

    /**
     * @dataProvider noticesBeforeExpirationProvider
     */
    public function testNoticesBeforeExpiration(int $days, int $userSetting, bool $send)
    {
        $this->dbBuilder->makeAccount(self::account($user = self::user(['EmailExpiration' => $userSetting]), [
            'ExpirationDate' => date_create(sprintf('+%d days', $days))->format('Y-m-d'),
        ], self::provider()));
        $this->executeCommand(array_merge(['--userId' => [$user->getId()]]));
        $this->logContains(sprintf('filter by userId: [%d]', $user->getId()));

        if ($send) {
            $this->logContains('sent 1 emails');
        } else {
            $this->logNotContains('sent 1 emails');
        }
    }

    public function noticesBeforeExpirationProvider(): array
    {
        return [
            [7, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [7, Usr::EMAIL_EXPIRATION_90_60_30_7, true],
            [6, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [6, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
            [5, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [5, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
            [4, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [4, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
            [3, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [3, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
            [2, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [2, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
            [1, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, true],
            [1, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
            [0, Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7, false],
            [0, Usr::EMAIL_EXPIRATION_90_60_30_7, false],
        ];
    }

    public function testExpiringNonNumericSubaccountBalance()
    {
        $account = self::account($user = self::user(), ['ExpirationDate' => null], self::provider([
            'Name' => 'IHG Hotels &amp; Resorts (One Rewards)',
            'DisplayName' => 'IHG Hotels &amp; Resorts (One Rewards)',
            'ShortName' => 'IHG Hotels &amp; Resorts (One Rewards)',
        ]));
        $account->setSubAccounts([
            new DbSubAccount('first', null, [], [
                'DisplayName' => 'Food &amp; Beverage Reward',
                'ExpirationDate' => date_create('+7 days')->format('Y-m-d'),
            ]),
        ]);
        $this->dbBuilder->makeAccount($account);
        $this->executeCommand(array_merge(['--userId' => [$user->getId()]]));
        $this->logContains(sprintf('filter by userId: [%d]', $user->getId()));
        $this->logContains('sent 1 emails');
    }

    /**
     * send push and email notifications for Amex Digital Entertainment Credit subaccount only if balance is 20.
     */
    public function testAmexDigitalEntertainmentCreditSubaccount()
    {
        $account = self::account($user = self::user(), ['ExpirationDate' => null, 'ProviderID' => 84]);
        $account->setSubAccounts([
            $subaccount = new DbSubAccount('first', 20, [], [
                'DisplayName' => 'Digital Entertainment Credit - card ending in 01010',
                'ExpirationDate' => date_create('+7 days')->format('Y-m-d'),
            ]),
        ]);
        $this->dbBuilder->makeAccount($account);
        $this->executeCommand(array_merge(['--userId' => [$user->getId()]]));
        $this->logContains(sprintf('filter by userId: [%d]', $user->getId()));
        $this->logContains('sent 1 emails');

        $this->clearLogs();
        $this->db->updateInDatabase('SubAccount', [
            'Balance' => 19,
        ], ['SubAccountID' => $subaccount->getId()]);
        $this->executeCommand(array_merge(['--userId' => [$user->getId()]]));
        $this->logContains(sprintf('filter by userId: [%d]', $user->getId()));
        $this->logContains('sent 0 emails');
    }

    public function testBlogPost()
    {
        $this->dbBuilder->makeAccount(self::account($user = self::user(), [
            'ExpirationDate' => date_create('+5 days')->format('Y-m-d'),
        ], self::provider([
            'BlogIdsMileExpiration' => '123',
        ])));
        $this->executeCommand(array_merge(['--userId' => [$user->getId()]]));
        $this->logContains(sprintf('filter by userId: [%d]', $user->getId()));
        $this->logContains('sent 1 emails');
    }

    /**
     * @dataProvider couponEmailNotificationsProvider
     */
    public function testCouponEmailNotifications($value, ?string $typeName, ?int $typeId)
    {
        $this->dbBuilder->makeProviderCoupon(
            new DbProviderCoupon(
                'Test Program',
                $value,
                $user = new User(null, false, [
                    'EmailExpiration' => 1,
                ]),
                PROVIDER_KIND_AIRLINE,
                [
                    'TypeName' => $typeName,
                    'TypeID' => $typeId,
                    'ExpirationDate' => date('Y-m-d', strtotime('+7 day')),
                ]
            )
        );
        $user = $this->em->getRepository(Usr::class)->find($user->getId());
        $this->assertAfterRun([$user->getEmail()], $user);
    }

    public function couponEmailNotificationsProvider(): array
    {
        return [
            [null, null, Providercoupon::TYPE_TICKET],
            [5, null, Providercoupon::TYPE_TICKET],
            [null, 'Test Cert', Providercoupon::TYPE_TICKET],
            [null, 'Test Cert', null],
            [5, 'Test Cert', Providercoupon::TYPE_TICKET],
            [5, 'Test Cert', null],
        ];
    }

    /**
     * @param DBUser|DBUserAgent $owner
     */
    private static function account($owner, array $accountFields = [], ?DBProvider $provider = null): DbAccount
    {
        $account = new DbAccount($owner, $provider);
        $account->extendFields(array_merge([
            'Balance' => 1000,
            'State' => ACCOUNT_ENABLED,
            'DontTrackExpiration' => 0,
            'ExpirationDate' => date_create('+7 days')->format('Y-m-d'),
        ], $accountFields));

        return $account;
    }

    private static function user(array $userFields = []): DBUser
    {
        return (new DBUser())->extendFields(array_merge([
            'EmailExpiration' => 1,
            'EmailFamilyMemberAlert' => 0,
        ], $userFields));
    }

    private static function business(array $businessFields = []): DBUser
    {
        return self::user(array_merge([
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
            'Company' => 'OOPS',
        ], $businessFields));
    }

    private static function family(DBUser $user, array $familyFields = []): DBUserAgent
    {
        $fm = DBUserAgent::familyMember($user, 'Jessica', 'Smith', StringHandler::getRandomCode(10) . '@mail.com');
        $fm->extendFields(array_merge([
            'SendEmails' => 1,
        ], $familyFields));

        return $fm;
    }

    private static function provider(array $providerFields = []): DBProvider
    {
        return (new DBProvider())->extendFields($providerFields);
    }

    private function createTestProvider()
    {
        return $this->aw->createAwProvider(null, null, [], [
            'Parse' => function () {
                $this->SetBalanceNA();
                $this->SetProperty('SubAccounts', [
                    [
                        'Code' => 'first',
                        'DisplayName' => 'First subaccount',
                        'Balance' => 1000,
                        'ExpirationDate' => strtotime('2024-01-01'),
                    ],
                    [
                        'Code' => 'second',
                        'DisplayName' => 'Second subaccount',
                        'Balance' => 2000,
                        'ExpirationDate' => strtotime('2014-01-01'),
                    ],
                ]);
            },
        ]);
    }

    private function runCommand(Usr $user, array $args = [])
    {
        if (!$user) {
            $user = $this->user;
        }
        $this->logs->clear();
        $this->executeCommand(array_merge([
            '--userId' => [$user->getId()],
            '--allowTestProvider' => true,
        ], $args));
    }

    private function assertAfterRun(array $emails, ?Usr $user = null)
    {
        $user = $user ?? $this->user;
        $this->runCommand($user);
        $this->logContains(sprintf('filter by userId: [%d]', $user->getId()));
        $this->logContains(sprintf('sent %d emails', count($emails)));

        foreach ($emails as $email) {
            $this->logContains(sprintf('mailing to %s', $email));
        }
    }

    private function createBusiness(array $admins = []): array
    {
        if (empty($admins)) {
            $admins[] = $this->user;
        }

        $business = $this->em->getRepository(Usr::class)->find(
            $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [
                'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
                'Company' => 'Oops',
            ], true)
        );

        foreach ($admins as $k => $admin) {
            if (!$admin) {
                $admins[$k] = $admin = $this->em->getRepository(Usr::class)->find(
                    $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [], true)
                );
            }

            $this->db->haveInDatabase('UserAgent', [
                'AgentID' => $admin->getId(),
                'ClientID' => $business->getId(),
                'AccessLevel' => ACCESS_ADMIN,
                'IsApproved' => 1,
            ]);
            $this->db->haveInDatabase('UserAgent', [
                'AgentID' => $business->getId(),
                'ClientID' => $admin->getId(),
                'AccessLevel' => ACCESS_WRITE,
                'IsApproved' => 1,
            ]);
        }

        return [$business, $admins];
    }

    private function createAccount(?Usr $user = null, ?Useragent $fm = null, array $accountFields = []): Account
    {
        $user = $user ?? $this->user;

        return $this->em->getRepository(Account::class)->find(
            $this->aw->createAwAccount($user->getId(), Aw::TEST_PROVIDER_ID, 'expiration.close', '', array_merge([
                'UserAgentID' => $fm ? $fm->getId() : null,
            ], $accountFields))
        );
    }

    private function createAccountWithSubaccounts(?Usr $user = null, ?Useragent $fm = null): array
    {
        $user = $user ?? $this->user;

        $account = $this->em->getRepository(Account::class)->find(
            $this->aw->createAwAccount($user->getId(), $this->createTestProvider(), 'expiration.close', '', [
                'UserAgentID' => $fm ? $fm->getId() : null,
            ])
        );
        $this->checkAccount($account->getAccountid());
        /** @var Subaccount $subAccount */
        $subAccount = $this->em->getRepository(Subaccount::class)->findOneBy([
            'accountid' => $account->getAccountid(),
            'code' => 'first',
        ]);
        $subAccount->setExpirationdate(new \DateTime('+7 day'));
        $this->em->flush();

        return [$account, $subAccount];
    }

    private function createCoupon(?Usr $user = null, ?Useragent $fm = null, string $expiration = '+7 days', bool $isPassport = false): Providercoupon
    {
        $user = $user ?? $this->user;

        return $this->em->getRepository(Providercoupon::class)->find(
            $this->aw->createAwCoupon($user->getId(), 'TestCoupon', '12345', 'My Test Coupon', [
                'ExpirationDate' => date('Y-m-d', strtotime($expiration)),
                'UserAgentID' => $fm ? $fm->getId() : null,
                'TypeID' => $isPassport ? Providercoupon::TYPE_PASSPORT : 0,
            ])
        );
    }

    private function createFamilyMember(?Usr $user = null): Useragent
    {
        $user = $user ?? $this->user;

        return $this->em->getRepository(Useragent::class)->find(
            $this->db->haveInDatabase('UserAgent', [
                'AgentID' => $user->getId(),
                'FirstName' => 'Ivan',
                'LastName' => 'Ivanov',
                'SendEmails' => '1',
                'Email' => 'test@test.com',
                'IsApproved' => 1,
            ])
        );
    }

    private function getUserEmail(): string
    {
        return $this->user->getEmail();
    }

    private function setEmailExpiration(bool $value, ?Usr $user = null): void
    {
        $user = $user ?? $this->user;
        $user->setEmailexpiration($value);
        $this->em->flush();
    }

    private function setDontTrackExpiration(Account $account, bool $value): void
    {
        $account->setDonttrackexpiration($value);
        $this->em->flush();
    }

    private function setEmailFamilyMemberAlert(bool $value, ?Usr $user = null): void
    {
        $user = $user ?? $this->user;
        $user->setEmailFamilyMemberAlert($value);
        $this->em->flush();
    }

    private function setAccountState(Account $account, int $value): void
    {
        $account->setState($value);
        $this->em->flush();
    }

    /**
     * @param Account|Subaccount $account
     */
    private function setAccountBalance($account, ?int $value): void
    {
        $account->setBalance($value);
        $this->em->flush();
    }

    private function setSubaccountHidden(Subaccount $subaccount, bool $value): void
    {
        $subaccount->setIsHidden($value);
        $this->em->flush();
    }

    private function setFamilyMemberEmail(Useragent $fm, ?string $email = null, bool $sendEmails = true): void
    {
        $fm->setEmail($email);
        $fm->setSendemails($sendEmails);
        $this->em->flush();
    }

    private function setDontSendEmailsSubaccExpDate(Account $account, bool $value): void
    {
        $account->getProviderid()->setDontSendEmailsSubaccExpDate($value);
        $this->em->flush();
    }

    private function checkAccount(int $accountId)
    {
        $this->aw->checkAccount($accountId);
        $this->db->executeQuery('
            UPDATE Account 
            SET 
                SuccessCheckDate = NOW() - INTERVAL 1 MINUTE,
                UpdateDate = NOW() - INTERVAL 1 MINUTE
            WHERE AccountID = ' . $accountId
        );
    }
}
