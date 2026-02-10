<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account as EntityAccount;
use AwardWallet\MainBundle\Entity\Fee as EntityFee;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Owner as EntityOwner;
use AwardWallet\MainBundle\Entity\PricingInfo as EntityPricingInfo;
use AwardWallet\MainBundle\Entity\Provider as EntityProvider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr as EntityUser;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Account as AccountSource;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Validator;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\BaseConverter;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\ItinerarySchema2EntityConverterInterface;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\LoggerFactory;
use AwardWallet\MainBundle\Service\Itinerary\SchemaBuilder;
use AwardWallet\Schema\Itineraries\Address as SchemaAddress;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\PricingInfo as SchemaPricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo as SchemaProviderInfo;
use AwardWallet\Schema\Itineraries\TravelAgency as SchemaTravelAgency;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

abstract class AbstractConverterTest extends BaseContainerTest
{
    protected ?TestLogger $logger = null;

    /**
     * @dataProvider restoringDeletedItineraryProvider
     */
    public function testRestoringDeletedItinerary(
        bool $expected,
        bool $isInitializedByUser,
        bool $silent,
        bool $entityCopied,
        bool $entityDeleted,
        bool $schemaCancelled
    ) {
        $entityItinerary = $this->getDefaultEntityItinerary();
        $schemaItinerary = $this->getSchemaItinerary();
        $options = $this->getEmailSavingOptions($isInitializedByUser, $silent);
        $entityItinerary->setCopied($entityCopied);
        $entityItinerary->setHidden($entityDeleted);
        $schemaItinerary->cancelled = $schemaCancelled;

        $this->getConverter()->convert($schemaItinerary, $entityItinerary, $options);
        $logText = 'restoring deleted itinerary because update initialized by user';

        if ($expected) {
            $this->assertLogContains($logText);
            $this->assertFalse($entityItinerary->getHidden());
        } else {
            $this->assertLogNotContains($logText);
            $this->assertEquals($entityDeleted, $entityItinerary->getHidden());
        }
    }

    public function restoringDeletedItineraryProvider(): array
    {
        return [
            [false, false, false, false, true, false],
            [false, true, true, false, true, false],
            [true, true, false, true, true, false],
            [false, true, false, false, false, false],
            [false, true, false, false, true, true],
            [true, true, false, false, true, false],
        ];
    }

    public function testMarkingItineraryAsParsedFromAccount()
    {
        $entityItinerary = $this->getDefaultEntityItinerary();
        $schemaItinerary = $this->getSchemaItinerary();
        $entityItinerary->setAccount(null);
        $entityItinerary->setRealProvider(null);
        $logText = fn (int $accountId): string => sprintf('marking itinerary as parsed from <Account %d>', $accountId);

        // options, empty account
        $this->getConverter()->convert($schemaItinerary, $entityItinerary, $this->getEmailSavingOptions());
        $this->assertLogNotContains($logText(1));
        $this->assertNull($entityItinerary->getAccount());
        $this->assertNull($entityItinerary->getRealProvider());

        $account = new EntityAccount();
        Tools::setValue($account, 'accountid', 1);
        $account->setProviderid($provider = new EntityProvider());
        $account->setOwner(new EntityOwner(new EntityUser()));
        $account2 = clone $account;
        Tools::setValue($account2, 'accountid', 2);

        // entity, empty account
        $entityItinerary->setAccount(null);
        $this->getConverter()->convert($schemaItinerary, $entityItinerary, $this->getAccountSavingOptions($account2));
        $this->assertLogContains($logText(2));
        $this->assertNotNull($entityItinerary->getAccount());
        $this->assertEquals($account2->getId(), $entityItinerary->getAccount()->getId());
        $this->assertNotNull($entityItinerary->getRealProvider());

        // entity, not equal accounts
        $entityItinerary->setAccount($account);
        $entityItinerary->setRealProvider(null);
        $this->getConverter()->convert($schemaItinerary, $entityItinerary, $this->getAccountSavingOptions($account2));
        $this->assertLogContains($logText(2));
        $this->assertNotNull($entityItinerary->getAccount());
        $this->assertEquals($account2->getId(), $entityItinerary->getAccount()->getId());
        $this->assertNotNull($entityItinerary->getRealProvider());

        // entity, empty provider
        $entityItinerary->setAccount($account2);
        $entityItinerary->setRealProvider(null);
        $this->getConverter()->convert($schemaItinerary, $entityItinerary, $this->getAccountSavingOptions($account2));
        $this->assertLogContains($logText(2));
        $this->assertNotNull($entityItinerary->getAccount());
        $this->assertEquals($account2->getId(), $entityItinerary->getAccount()->getId());
        $this->assertNotNull($entityItinerary->getRealProvider());
    }

    public function testModifiedFlagWillBeRemoved()
    {
        $entityItinerary = $this->getDefaultEntityItinerary();
        $entityItinerary->setModified(true);
        $this->getConverter()->convert(
            $this->getSchemaItinerary(),
            $entityItinerary,
            $this->getEmailSavingOptions()
        );
        $this->assertLogContains('modified flag will be removed');
        $this->assertFalse($entityItinerary->getModified());
    }

    /**
     * @dataProvider changingItineraryProviderProvider
     */
    public function testChangingItineraryProvider(
        bool $expected,
        ?string $schemaProviderCode,
        ?int $entityProviderKind,
        bool $foundProvider,
        ?int $foundProviderKind = null
    ) {
        $schemaItinerary = $this->getSchemaItinerary();
        $entityItinerary = $this->getDefaultEntityItinerary();
        $entityItinerary->setRealProvider(null);
        $schemaItinerary->providerInfo->code = $schemaProviderCode;

        $provider = new EntityProvider();
        Tools::setValue($provider, 'providerid', 1);
        $provider->setCode('provider_1');
        $provider2 = new EntityProvider();
        Tools::setValue($provider2, 'providerid', 2);
        $provider2->setCode('provider_2');

        if (is_null($entityProviderKind)) {
            $entityItinerary->setRealProvider(null);
        } else {
            $provider->setKind($entityProviderKind);
            $entityItinerary->setRealProvider($provider);
        }

        if ($foundProvider) {
            $provider2->setKind($foundProviderKind);
            $converter = $this->getConverter([], ['findOneBy' => $provider2]);
        } else {
            $converter = $this->getConverter();
        }

        $converter->convert($schemaItinerary, $entityItinerary, $this->getEmailSavingOptions());

        $logText = 'changing itinerary provider from provider_1 to provider_2';

        if ($expected) {
            $this->assertLogContains($logText);
            $this->assertNotNull($entityItinerary->getRealProvider());
            $this->assertEquals(2, $entityItinerary->getRealProvider()->getId());
        } else {
            $this->assertLogNotContains($logText);

            if (is_null($entityProviderKind)) {
                $this->assertNull($entityItinerary->getRealProvider());
            } else {
                $this->assertNotNull($entityItinerary->getRealProvider());
                $this->assertEquals(1, $entityItinerary->getRealProvider()->getId());
            }
        }
    }

    public function changingItineraryProviderProvider(): array
    {
        return [
            [
                false, null, null, false,
            ],
            [
                false, 'code', null, false,
            ],
            [
                false, 'code', PROVIDER_KIND_HOTEL, false,
            ],
            [
                false, 'code', PROVIDER_KIND_OTHER, false,
            ],
            [
                false, 'code', PROVIDER_KIND_OTHER, true, PROVIDER_KIND_OTHER,
            ],
            [
                true, 'code', PROVIDER_KIND_OTHER, true, PROVIDER_KIND_AIRLINE,
            ],
            [
                true, 'code', PROVIDER_KIND_CREDITCARD, true, PROVIDER_KIND_AIRLINE,
            ],
            [
                true, 'code', PROVIDER_KIND_CREDITCARD, true, PROVIDER_KIND_CAR_RENTAL,
            ],
            [
                true, 'code', PROVIDER_KIND_CREDITCARD, true, PROVIDER_KIND_HOTEL,
            ],
            [
                true, 'code', PROVIDER_KIND_CREDITCARD, true, PROVIDER_KIND_CRUISES,
            ],
            [
                true, 'code', PROVIDER_KIND_CREDITCARD, true, PROVIDER_KIND_TRAIN,
            ],
        ];
    }

    public function testCancel()
    {
        $schemaItinerary = $this->getSchemaItinerary();
        $entityItinerary = $this->getDefaultEntityItinerary();
        $entityItinerary->setCancelled(false);
        $entityItinerary->setHidden(false);
        $schemaItinerary->cancelled = true;
        $this->getConverter()->convert($schemaItinerary, $entityItinerary, $this->getEmailSavingOptions());
        $this->assertLogContains('cancelling itinerary');
        $this->assertTrue($entityItinerary->getCancelled());
        $this->assertTrue($entityItinerary->getHidden());
    }

    /**
     * @dataProvider modesProvider
     */
    public function testBaseConvert(bool $update)
    {
        $schemaItinerary = $this->getSchemaItinerary();

        if ($update) {
            $entityItinerary = $this->getDefaultEntityItinerary();
        }

        $provider = new EntityProvider();
        Tools::setValue($provider, 'providerid', 1);
        $provider->setCode('provider_1');
        $provider2 = new EntityProvider();
        Tools::setValue($provider2, 'providerid', 2);
        $provider2->setCode('provider_2');
        $account = new EntityAccount();
        Tools::setValue($account, 'accountid', 1);
        $account->setProviderid($provider);
        $account->setOwner(new EntityOwner(new EntityUser()));

        $entityItinerary = $this->getConverter()->convert(
            $schemaItinerary,
            $entityItinerary ?? null,
            $this->getAccountSavingOptions($account)
        );

        if ($update) {
            $this->assertNotNull($entityItinerary->getUpdateDate());
            $this->assertEquals('old spent awards', $entityItinerary->getPricingInfo()->getSpentAwards());
        } else {
            $this->assertNull($entityItinerary->getUpdateDate());
            $this->assertNotNull($entityItinerary->getRealProvider());
            $this->assertEquals(1, $entityItinerary->getRealProvider()->getId());
            $this->assertNotNull($entityItinerary->getAccount());
            $this->assertEquals(1, $entityItinerary->getAccount()->getId());
            $this->assertLogContains('provider <Provider provider_1> from <Account 1>');
            $this->assertNotNull($entityItinerary->getFirstSeenDate());
            $this->assertNull($entityItinerary->getTravelAgency());
            $this->assertNull($entityItinerary->getPricingInfo()->getSpentAwards());
        }

        $this->assertEquals('Earned rewards', $entityItinerary->getPricingInfo()->getTravelAgencyEarnedAwards());
        $this->assertNotNull($entityItinerary->getUser());
        $this->assertSame(900.0, $entityItinerary->getPricingInfo()->getCost());
        $this->assertEquals('USD', $entityItinerary->getPricingInfo()->getCurrencyCode());
        $this->assertSame(100.0, $entityItinerary->getPricingInfo()->getDiscount());
        $this->assertEquals('Earned rewards 2', $entityItinerary->getPricingInfo()->getEarnedAwards());
        $this->assertEquals([new EntityFee('Taxes', 333.0)], $entityItinerary->getPricingInfo()->getFees());
        $this->assertSame(333.0, $entityItinerary->getPricingInfo()->getFeesTotal());
        $this->assertNull($entityItinerary->getPricingInfo()->getTax());
        $this->assertSame(1000.0, $entityItinerary->getPricingInfo()->getTotal());

        if ($entityItinerary instanceof SourceListInterface) {
            $this->assertCount(1, $entityItinerary->getSources());
            $this->assertInstanceOf(AccountSource::class, current($entityItinerary->getSources()));
        }

        $this->assertEquals(['SCHEMA_CONF_TA_1', 'SCHEMA_CONF_TA_2'], $entityItinerary->getTravelAgencyConfirmationNumbers());
        $this->assertEquals(['777-666', '333-222'], $entityItinerary->getTravelAgencyPhones());
        $this->assertEquals(['12345', '***65'], $entityItinerary->getTravelAgencyParsedAccountNumbers());
        $this->assertInstanceOf(EntityPricingInfo::class, $entityItinerary->getPricingInfo());
        $this->assertEquals('Confirmed 2', $entityItinerary->getParsedStatus());
        $this->assertNotNull($entityItinerary->getReservationDate());
        $this->assertEquals('2000-01-01 10:00:00', $entityItinerary->getReservationDate()->format('Y-m-d H:i:s'));
        $this->assertEquals([777], $entityItinerary->getParsedAccountNumbers());
        $this->assertEquals('New cancellation policy', $entityItinerary->getCancellationPolicy());
        $this->assertEquals('new comment', $entityItinerary->getComment());
    }

    public function modesProvider(): array
    {
        return [
            'update' => [true],
            'create' => [false],
        ];
    }

    protected function getBaseConverter(array $providerRep = []): BaseConverter
    {
        return $this->construct(
            BaseConverter::class,
            [
                new LoggerFactory($this->getLogger()),
                $this->makeEmpty(ProviderRepository::class, $providerRep),
                $this->makeEmpty(Validator::class, ['getLiveSources' => fn (array $sources) => $sources]),
            ]
        );
    }

    protected function getHelper(array $geo = []): Helper
    {
        return $this->construct(
            Helper::class,
            [
                $this->makeEmpty(GoogleGeo::class, $geo),
                new LoggerFactory($this->getLogger()),
            ],
            [
                'convertAddress2GeoTag' => fn (string $address) => (new Geotag())->setAddress($address),
            ]
        );
    }

    protected function assertLogContains(string $str)
    {
        $this->assertStringContainsString($str, $this->getLogs());
    }

    protected function assertLogNotContains(string $str)
    {
        $this->assertStringNotContainsString($str, $this->getLogs());
    }

    protected function getLogger(bool $new = false): LoggerInterface
    {
        if (is_null($this->logger) || $new) {
            return $this->logger = new TestLogger();
        }

        return $this->logger;
    }

    protected function getLogs(): string
    {
        return implode("\n", array_column($this->logger->records, 'message'));
    }

    protected function getEmailSavingOptions(bool $initByUser = true, bool $silent = false): SavingOptions
    {
        return SavingOptions::savingByEmail(
            new EntityOwner(new EntityUser()),
            'abc',
            new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com'),
            $initByUser,
            $silent
        );
    }

    protected function getAccountSavingOptions(EntityAccount $account, bool $initByUser = true): SavingOptions
    {
        return SavingOptions::savingByAccount($account, $initByUser);
    }

    protected function setupEntityItinerary(EntityItinerary $entityItinerary): void
    {
        Tools::setValue($entityItinerary, 'id', 1);
        $entityItinerary->setUser(new EntityUser());
        $entityItinerary->setRealProvider(new EntityProvider());
        $entityItinerary->setConfFields(['CONF_FIELDS']);
        $entityItinerary->setComment('Comment');
        $entityItinerary->setCreateDate(new \DateTime('2000-01-01 00:00:00'));
        $entityItinerary->setCancelled(false);
        $entityItinerary->setConfirmationNumber('CONF');
        $entityItinerary->setProviderConfirmationNumbers(['P_CONF']);
        $entityItinerary->setTravelAgencyConfirmationNumbers(['TA_CONF']);
        $entityItinerary->setPhone('phone');
        $entityItinerary->setParsedAccountNumbers(['AN_01', 'AN_02']);
        $entityItinerary->setTravelAgencyParsedAccountNumbers(['TA_AN_1', 'TA_AN_2']);
        $entityItinerary->setTravelAgencyPhones(['TA_PHONE_1', 'TA_PHONE_2']);
        $entityItinerary->setPricingInfo(
            new EntityPricingInfo(
                99.12,
                'USD',
                null,
                [new EntityFee('Taxes', 6.03)],
                105.15,
                'old spent awards',
                'old earned awards',
                'old traveling agency earned awards'
            )
        );
        $entityItinerary->setReservationDate(new \DateTime('2000-01-01 00:00:00'));
        $entityItinerary->setTravelerNames(['John Smith']);
        $entityItinerary->setCancellationPolicy('Ticket is non-refundable');
        $entityItinerary->setParsedStatus('Confirmed');
        $entityItinerary->setLastParseDate(new \DateTime('2000-01-01 00:00:00'));
    }

    abstract protected function getConverter(
        array $geo = [],
        array $providerRep = []
    ): ItinerarySchema2EntityConverterInterface;

    abstract protected function getDefaultEntityItinerary(): EntityItinerary;

    abstract protected function getSchemaItinerary(): SchemaItinerary;

    protected function getSchemaTravelAgency(): SchemaTravelAgency
    {
        return SchemaBuilder::makeSchemaTravelAgency(
            SchemaBuilder::makeSchemaProviderInfo(
                'schema_provider',
                'Travel Agency',
                [
                    SchemaBuilder::makeSchemaParsedNumber('12345'),
                    SchemaBuilder::makeSchemaParsedNumber('***65', true),
                ],
                'Earned rewards'
            ),
            [
                SchemaBuilder::makeSchemaConfNo('SCHEMA_CONF_TA_1'),
                SchemaBuilder::makeSchemaConfNo('SCHEMA_CONF_TA_2', true),
            ],
            [
                SchemaBuilder::makeSchemaPhoneNumber('777-666', 'description'),
                SchemaBuilder::makeSchemaPhoneNumber('333-222'),
            ]
        );
    }

    protected function getSchemaPricingInfo(): SchemaPricingInfo
    {
        return SchemaBuilder::makeSchemaPricingInfo(
            1000,
            900,
            100,
            'spent awards',
            'USD',
            [
                SchemaBuilder::makeSchemaFee('Taxes', 333),
            ]
        );
    }

    protected function getSchemaStatus(): string
    {
        return 'Confirmed 2';
    }

    protected function getSchemaReservationDate(): \DateTime
    {
        return new \DateTime('2000-01-01 10:00:00');
    }

    protected function getSchemaProviderInfo(): SchemaProviderInfo
    {
        return SchemaBuilder::makeSchemaProviderInfo(
            'schema_provider_2',
            'Provider',
            [
                SchemaBuilder::makeSchemaParsedNumber('777', true),
            ],
            'Earned rewards 2'
        );
    }

    protected function getSchemaCancellationPolicy(): string
    {
        return 'New cancellation policy';
    }

    protected function getSchemaNotes(): string
    {
        return 'new comment';
    }

    protected function getSchemaAddress(
        string $text = 'Address',
        ?string $addressLine = 'address line',
        ?string $city = 'city',
        ?string $stateName = 'state name',
        ?string $countryName = 'country name',
        ?string $countryCode = 'country code',
        ?string $postalCode = 'postal code',
        ?float $lat = 100.0,
        ?float $lng = 200.0,
        ?int $timezone = null,
        ?string $timezoneId = null
    ): SchemaAddress {
        return SchemaBuilder::makeSchemaAddress(
            $text,
            $addressLine,
            $city,
            $stateName,
            $countryName,
            $countryCode,
            $postalCode,
            $lat,
            $lng,
            $timezone,
            $timezoneId
        );
    }
}
