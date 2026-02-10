<?php

namespace AwardWallet\Tests\Unit\MainBundle\Manager\AccountListManager;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\Blog\BlogPost;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Clock\ClockNative;
use Clock\ClockTest;
use Codeception\Module\JsonNormalizer;
use Duration\Duration;
use Herrera\Version\Parser;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

use function PHPUnit\Framework\assertFalse;

/**
 * @group frontend-unit
 */
class MobileMapperTest extends BaseContainerTest
{
    private const BASE_JSON_PATH = 'account/mobile/details/';
    private ?JsonNormalizer $jsonNormalizer;
    private ?\DateTimeImmutable $baseDate;

    public function _before()
    {
        parent::_before();

        $this->jsonNormalizer = $this->getModule('JsonNormalizer');
        $this->baseDate = new \DateTimeImmutable('2023-06-01 12:00:00');
        $this->mockService(
            ClockNative::class,
            new ClockTest(Duration::fromDateTime($this->baseDate))
        );
        $this->mockService(BlogPost::class, $this->makeEmpty(BlogPost::class, [
            'fetchPostById' => function ($postIds, $withoutCache, $options) {
                $post = [
                    'title' => 'Do Hilton Points Expire?',
                    'postURL' => 'https://awardwallet.com/blog/do-hilton-honors-points-expire/',
                    'imageURL' => '',
                ];

                $posts = [];

                foreach ($postIds as $postId) {
                    $posts[$postId] = array_merge(['id' => $postId], $post);
                }

                return $posts;
            },
        ]));
    }

    public function _after()
    {
        parent::_after();

        $this->jsonNormalizer = null;
        $this->baseDate = null;
    }

    public function testCouponLinkedToAccount()
    {
        $userId = $this->aw->createAwUser('tlc' . StringUtils::getPseudoRandomString(8));
        $providerId = $this->aw->createAwProvider($name = 'tlc' . StringUtils::getPseudoRandomString(8), $name);
        $accountId = $this->aw->createAwAccount($userId, $providerId, 'somelogin');
        $couponId = $this->aw->createAwCoupon($userId, 'Some Name', 100500, '', [
            'AccountID' => $accountId,
            'TypeID' => Providercoupon::TYPE_CERTIFICATE,
        ]);
        $accounts = $this->getAccounts($userId, '4.42.0');
        $this->aw->assertArrayContainsArray(
            [
                'c' . $couponId => [
                    'ID' => $couponId,
                    'ParentAccount' => $accountId,
                    'CouponType' => 'Certificate',
                ],
            ],
            $accounts
        );
    }

    public function testSharedCouponShouldNotContainLinkToNonSharedAccount()
    {
        $userId = $this->aw->createAwUser();
        $connectedUserId = $this->aw->createAwUser();

        $connectionId = $this->aw->createConnection($connectedUserId, $userId, 1, null, ['AccessLevel' => Useragent::ACCESS_WRITE]);
        $this->aw->createConnection($userId, $connectedUserId, 1, null, ['AccessLevel' => Useragent::ACCESS_WRITE]);

        $providerId = $this->aw->createAwProvider($name = 'tlc' . StringUtils::getPseudoRandomString(8), $name);
        $accountId = $this->aw->createAwAccount($connectedUserId, $providerId, 'somelogin');
        $couponId = $this->aw->createAwCoupon($connectedUserId, 'Some Name', 100500, '', [
            'AccountID' => $accountId,
            'TypeID' => Providercoupon::TYPE_CERTIFICATE,
        ]);
        $this->aw->shareAwCouponByConnection($couponId, $connectionId);

        $accounts = $this->getAccounts($userId, '4.42.0');
        assertFalse(isset($accounts['c' . $couponId]['ParentAccount']));
    }

    /**
     * @dataProvider mobileVersionsDataProvider
     */
    public function testDocumentVaccine(string $mobileVersion)
    {
        $userId = $this->aw->createAwUser('vcc' . StringUtils::getPseudoRandomString(8));
        $couponId = $this->aw->createAwCoupon($userId, 'Vaccine Card', null, 'some comment', [
            'ProgramName' => 'Vaccine Card',
            'TypeID' => Providercoupon::TYPE_VACCINE_CARD,
            'Kind' => PROVIDER_KIND_DOCUMENT,
            'CustomFields' => \json_encode([
                'vaccineCard' => [
                    'disease' => 'Covid 19',
                    'firstDoseDate' => [
                        'date' => '2023-11-01 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'firstDoseVaccine' => 'Pfizer',
                    'secondDoseDate' => [
                        'date' => '2023-11-22 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'secondDoseVaccine' => 'Moderna',
                    'boosterDate' => [
                        'date' => '2023-11-25 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'boosterVaccine' => 'Sputnik',
                    'secondBoosterDate' => [
                        'date' => '2023-11-30 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'secondBoosterVaccine' => 'Covivak',
                    'passportName' => 'Alexi Vereschaga',
                    'dateOfBirth' => [
                        'date' => '2007-11-01 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'passportNumber' => '100500',
                    'certificateIssued' => [
                        'date' => '2023-11-30 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'countryIssue' => '17',
                ],
            ]),
        ]);
        $accounts = $this->getAccounts($userId, $mobileVersion);
        $document = $accounts["c{$couponId}"] ?? null;
        $this->assertNotEmpty($document);
        $this->jsonNormalizer->expectJsonTemplate(
            self::fromRelativePath("document/{$mobileVersion}/vaccine.json"),
            \json_encode($document),
            [
                'couponId' => $couponId,
                'userId' => $userId,
                'FID' => "c{$couponId}",
            ]
        );
        $this->assertNotEmpty($accounts);
    }

    /**
     * @return list<array{string}>
     */
    public function mobileVersionsDataProvider(): array
    {
        return [
            ['4.42.0'],
            ['4.47.0'],
        ];
    }

    /**
     * @dataProvider mobileVersionsDataProvider
     */
    public function testCustomAccountExpirationFar(string $mobileVersion)
    {
        $userId = $this->aw->createAwUser('vcc' . StringUtils::getPseudoRandomString(8));
        $accountId = $this->aw->createAwAccount($userId, null, 'somelogin', 'some pass', [
            'ProgramName' => 'American Airlines (AAdvantage)',
            'Login2' => 'somelogin2',
            'Kind' => PROVIDER_KIND_AIRLINE,
            'comment' => 'some comment',
            'LoginURL' => 'https://someurl.local',
            'ExpirationAutoSet' => EXPIRATION_USER,
            'Balance' => 100500,
            'CurrencyID' => Currency::POINTS_ID,
            'CustomEliteLevel' => 'silver',
            'DontTrackExpiration' => 0,
            'ExpirationDate' => $this->baseDate->modify('+3 months')->format('Y-m-d H:i:s'),
        ]);
        $accounts = $this->getAccounts($userId, $mobileVersion);
        $account = $accounts["a{$accountId}"] ?? null;
        $this->assertNotEmpty($account);
        $this->jsonNormalizer->expectJsonTemplate(
            self::fromRelativePath("account/{$mobileVersion}/custom_expiration_far.json"),
            \json_encode($account),
            [
                'accountId' => $accountId,
                'userId' => $userId,
                'FID' => "a{$accountId}",
            ]
        );
        $this->assertNotEmpty($accounts);
    }

    /**
     * @dataProvider mobileVersionsDataProvider
     */
    public function testTestProviderExpirationOn(string $mobileVersion)
    {
        $providerId = $this->aw->createAwProvider($name = 'tlc_' . StringUtils::getPseudoRandomString(8), $name, [
            'State' => PROVIDER_ENABLED,
            'Kind' => PROVIDER_KIND_HOTEL,
            'DisplayName' => 'Hilton Honors ' . StringUtils::getRandomCode(8),
            'ProgramName' => 'Hilton Honors',
            'LoginURL' => 'https://www.hilton.com/en/hilton-honors/login/',
            'ExpirationAlwaysKnown' => 1,
            'CanCheckExpiration' => 1,
            'ExpirationDateNote' => 'Hilton Honors states the following on their website: &quot;<a href="https://hiltonhonors3.hilton.com/en/terms/index.html" target="_blank">Members who do not have eligible activity as defined in a. - d. below in any 24 consecutive month period may be removed from the Program and are subject to forfeiture of all accumulated Points.</a>&quot;',
            'ExpirationUnknownNote' => 'We calculate the expiration date based on your <a href="http://secure3.hilton.com/en/hh/customer/account/allPointActivity.htm" target="_blank">activity</a>, however, it seems that there is no information regarding your activity.',
            'BlogIdsMileExpiration' => 10005,
        ]);
        $userId = $this->aw->createAwUser('tst' . StringUtils::getPseudoRandomString(8));
        $accountId = $this->aw->createAwAccount($userId, $providerId, 'expiration.on', 'some pass', [
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 1800,
            'TotalBalance' => 1800,
            'LoginURL' => 'https://someurl.local',
            'CreationDate' => $this->baseDate->format('Y-m-d H:i:s'),
            'PassChangeDate' => $this->baseDate->format('Y-m-d H:i:s'),
            'UpdateDate' => $this->baseDate->modify('+30 seconds')->format('Y-m-d H:i:s'),
            'QueueDate' => $this->baseDate->format('Y-m-d H:i:s'),
            'ModifyDate' => $this->baseDate->format('Y-m-d H:i:s'),
            'ErrorDate' => $this->baseDate->format('Y-m-d H:i:s'),
            'SuccessCheckDate' => $this->baseDate->modify('+30 seconds')->format('Y-m-d H:i:s'),
            'ExpirationDate' => $this->baseDate->modify("+1 year +6 month")->format('Y-m-d H:i:s'),
            'ExpirationAutoSet' => EXPIRATION_AUTO,
            'DontTrackExpiration' => 0,
        ]);

        $accounts = $this->getAccounts($userId, $mobileVersion);
        $account = $accounts["a{$accountId}"] ?? null;
        /** @var Provider $provider */
        $provider = $this->em->getRepository(Provider::class)->find($providerId);
        $this->assertNotEmpty($account);
        $this->jsonNormalizer->expectJsonTemplate(
            self::fromRelativePath("account/{$mobileVersion}/test_provider_expiration_on.json"),
            \json_encode($account),
            [
                'providerCode' => $provider->getCode(),
                'providerID' => $provider->getId(),
                'accountId' => $accountId,
                'userId' => $userId,
                'FID' => "a{$accountId}",
                'displayName' => $provider->getDisplayname(),
            ]
        );
        $this->assertNotEmpty($accounts);
    }

    public function testDocumentPassport(string $mobileVersion = '4.50.0')
    {
        $isExists = $this->db->grabFromDatabase('ProviderCouponType', 'BlogIdsMileExpiration', ['TypeID' => Providercoupon::TYPE_PASSPORT]);

        if (empty($isExists)) {
            $this->db->haveInDatabase(
                'ProviderCouponType',
                ['TypeID' => Providercoupon::TYPE_PASSPORT, 'BlogIdsMileExpiration' => 10005]
            );
        }
        $userId = $this->aw->createAwUser('custpassport' . StringUtils::getPseudoRandomString(8));
        $accountId = $this->aw->createAwAccount($userId, Provider::CITI_ID, 'tstlogin' . StringUtils::getPseudoRandomString(8));
        $couponId = $this->aw->createAwCoupon($userId, 'Passport', null, 'comment', [
            'ProgramName' => 'Passport',
            'TypeID' => Providercoupon::TYPE_PASSPORT,
            'Kind' => PROVIDER_KIND_DOCUMENT,
            'ExpirationDate' => date('c', $this->baseDate->modify('+10 months')->getTimestamp()),
            'CustomFields' => \json_encode([
                Providercoupon::FIELD_KEY_PASSPORT => [
                    'name' => 'Jon Smith',
                    'number' => '12345678',
                    'issueDate' =>
                        [
                            'date' => '2022-06-01 00:00:00',
                            'timezone_type' => 3,
                            'timezone' => 'UTC',
                        ],
                    'country' => Country::UNITED_STATES,
                ],
            ]),
        ]);
        $accounts = $this->getAccounts($userId, $mobileVersion);
        $document = $accounts["c{$couponId}"] ?? null;

        $this->assertNotEmpty($document);
        $this->jsonNormalizer->expectJsonTemplate(
            self::fromRelativePath("document/{$mobileVersion}/us-passport.json"),
            \json_encode($document),
            [
                'couponId' => $couponId,
                'userId' => $userId,
                'FID' => "c{$couponId}",
            ]
        );
        $this->assertNotEmpty($accounts);
    }

    public function testDocumentPriorityPass(string $mobileVersion = '4.52.0')
    {
        $isExists = $this->db->grabFromDatabase('ProviderCouponType', 'BlogIdsMileExpiration', ['TypeID' => Providercoupon::TYPE_PASSPORT]);

        if (empty($isExists)) {
            $this->db->haveInDatabase(
                'ProviderCouponType',
                ['TypeID' => Providercoupon::TYPE_PRIORITY_PASS, 'BlogIdsMileExpiration' => 10005]
            );
        }
        $userId = $this->aw->createAwUser('custprpass' . StringUtils::getPseudoRandomString(8));
        $ccId = $this->db->haveInDatabase('CreditCard', [
            'ProviderID' => $this->aw->createAwProvider(),
            'Name' => 'Test Card',
            'IsBusiness' => 1,
            'MatchingOrder' => 1,
            'CardFullName' => 'Test Card Fullname',
            'VisibleOnLanding' => 1,
            'VisibleInList' => 1,
            'DirectClickURL' => 'http://test-card.com',
            'Text' => 'description',
            'PictureVer' => '123',
            'PictureExt' => 'jpg',
            'SortIndex' => 0,
        ]);

        $couponId = $this->aw->createAwCoupon($userId, 'PriorityPass', null, 'comment', [
            'ProgramName' => 'Passport',
            'TypeID' => Providercoupon::TYPE_PRIORITY_PASS,
            'Kind' => PROVIDER_KIND_DOCUMENT,
            'ExpirationDate' => $expDate = date('c', $this->baseDate->modify('+10 months')->getTimestamp()),
            'CustomFields' => \json_encode([
                Providercoupon::FIELD_KEY_PRIORITY_PASS => [
                    'accountNumber' => '100500',
                    'expirationDate' => $expDate,
                    'isSelect' => true,
                    'creditCardId' => $ccId,
                ],
            ]),
        ]);
        $accounts = $this->getAccounts($userId, $mobileVersion);
        $document = $accounts["c{$couponId}"] ?? null;

        $this->assertNotEmpty($document);
        $this->jsonNormalizer->expectJsonTemplate(
            self::fromRelativePath("document/{$mobileVersion}/priority-pass.json"),
            \json_encode($document),
            [
                'couponId' => $couponId,
                'userId' => $userId,
                'FID' => "c{$couponId}",
                "creditCardId" => $ccId,
            ]
        );
        $this->assertNotEmpty($accounts);
    }

    public function testTrustedTraveler(string $mobileVersion = '4.52.0')
    {
        $isExists = $this->db->grabFromDatabase('ProviderCouponType', 'BlogIdsMileExpiration', ['TypeID' => Providercoupon::TYPE_PASSPORT]);

        if (empty($isExists)) {
            $this->db->haveInDatabase(
                'ProviderCouponType',
                ['TypeID' => Providercoupon::TYPE_PRIORITY_PASS, 'BlogIdsMileExpiration' => 10005]
            );
        }
        $userId = $this->aw->createAwUser('trusttrvlr' . StringUtils::getPseudoRandomString(8));

        $couponId = $this->aw->createAwCoupon($userId, 'Trusted', null, 'comment', [
            'ProgramName' => 'Passport',
            'TypeID' => Providercoupon::TYPE_TRUSTED_TRAVELER,
            'Kind' => PROVIDER_KIND_DOCUMENT,
            'ExpirationDate' => $expDate = date('c', $this->baseDate->modify('+10 months')->getTimestamp()),
            'CustomFields' => \json_encode([
                Providercoupon::FIELD_KEY_TRUSTED_TRAVELER => [
                    'travelerNumber' => '100500',
                ],
            ]),
        ]);
        $accounts = $this->getAccounts($userId, $mobileVersion);
        $document = $accounts["c{$couponId}"] ?? null;

        $this->assertNotEmpty($document);
        $this->jsonNormalizer->expectJsonTemplate(
            self::fromRelativePath("document/{$mobileVersion}/trusted-traveler.json"),
            \json_encode($document),
            [
                'couponId' => $couponId,
                'userId' => $userId,
                'FID' => "c{$couponId}",
            ]
        );
        $this->assertNotEmpty($accounts);
    }

    protected function getAccounts(int $userId, string $mobileVersion): array
    {
        $versioning = $this->container->get('aw.api.versioning');
        $versioning->setVersion(Parser::toVersion($mobileVersion));
        $versioning->setVersionsProvider(new MobileVersions(MobileVersions::ANDROID));

        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);
        $this->container->get('aw.security.token_storage')->setToken(new PostAuthenticationGuardToken($user, 'secured_area', $user->getRoles()));
        $listOptions = $this->container->get(OptionsFactory::class)
            ->createMobileOptions()
            ->set(Options::OPTION_USER, $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId))
            ->set(Options::OPTION_LOAD_BLOG_POSTS, true);
        $accounts = $this->container->get(AccountListManager::class)->getAccountList($listOptions)->getAccounts();

        return $accounts;
    }

    private static function fromRelativePath(string $path)
    {
        return codecept_data_dir(self::BASE_JSON_PATH . $path);
    }
}
