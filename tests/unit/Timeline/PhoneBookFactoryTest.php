<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Provider as EntityProvider;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\PhoneBookFactory;
use AwardWallet\MainBundle\Timeline\PhonesSection;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\AccountProperty;
use AwardWallet\Tests\Modules\DbBuilder\Alliance;
use AwardWallet\Tests\Modules\DbBuilder\AllianceEliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\EliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderPhone;
use AwardWallet\Tests\Modules\DbBuilder\ProviderProperty;
use AwardWallet\Tests\Modules\DbBuilder\Reservation;
use AwardWallet\Tests\Modules\DbBuilder\TextEliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Unit\BaseContainerTest;

class PhoneBookFactoryTest extends BaseContainerTest
{
    /**
     * @dataProvider eliteLevelPhoneProvider
     */
    public function testEliteLevelPhone(string $eliteLevel, string $expectedPhone, ?string $expectedLevelName)
    {
        $providerPhoneDefault = new ProviderPhone(
            '2-33-66-000',
            $provider = new Provider(),
        );
        $this->dbBuilder->makeProviderPhone($providerPhoneDefault);

        $providerPhone1 = new ProviderPhone(
            '2-33-66',
            $provider,
            new EliteLevel(
                1,
                'Elite Level 1',
                $provider,
                new AllianceEliteLevel(1, 'Alliance Elite Level 1', new Alliance('Alliance 1', 'A1')),
                [
                    new TextEliteLevel('Level 1'),
                ]
            )
        );
        $this->dbBuilder->makeProviderPhone($providerPhone1);

        $providerPhone2 = new ProviderPhone(
            '2-33-10',
            $provider,
            new EliteLevel(
                2,
                'Elite Level 2',
                $provider,
                new AllianceEliteLevel(2, 'Alliance Elite Level 2', new Alliance('Alliance 1', 'A1')),
                [
                    new TextEliteLevel('Level 2'),
                ]
            )
        );
        $this->dbBuilder->makeProviderPhone($providerPhone2);
        $reservation = new Reservation(
            'ABC001',
            'Test Hotel',
            date_create('+5 days'),
            date_create('+7 days'),
            $user = new User()
        );
        $reservation->setAccount(
            new Account(
                $user,
                $provider,
                [
                    new AccountProperty(ProviderProperty::createTyped('status', null, PROPERTY_KIND_STATUS), $eliteLevel),
                ]
            )
        );

        $this->dbBuilder->makeItinerary($reservation);
        $factory = $this->container->get(PhoneBookFactory::class);
        $itinerary = $this->em->find(EntityReservation::class, $reservation->getId());
        $user = $this->em->find(Usr::class, $user->getId());
        $phones = $factory->create([$itinerary], $user)->getPhones();

        $this->assertNotEmpty($phones);
        $this->assertArrayHasKey(PhonesSection::SECTION_ACCOUNT, $phones);
        $this->assertInstanceOf(EntityProvider::class, $phones[PhonesSection::SECTION_ACCOUNT]->getOrigin());
        $this->assertNotEmpty($phones[PhonesSection::SECTION_ACCOUNT]->getAddressBookPhones());

        if (empty($expectedLevelName)) {
            $this->assertCount(1, $phones[PhonesSection::SECTION_ACCOUNT]->getAddressBookPhones());
            $this->assertEquals($expectedPhone, $phones[PhonesSection::SECTION_ACCOUNT]->getAddressBookPhones()[0]['Phone']);
            $this->assertNull($phones[PhonesSection::SECTION_ACCOUNT]->getAddressBookPhones()[0]['EliteLevel']);
        } else {
            $eliteLevels = array_map(function ($phone) {
                return $phone['EliteLevel'];
            }, $phones[PhonesSection::SECTION_ACCOUNT]->getAddressBookPhones());
            $elitePhones = array_map(function ($phone) {
                return $phone['Phone'];
            }, $phones[PhonesSection::SECTION_ACCOUNT]->getAddressBookPhones());

            $this->assertContains($expectedLevelName, $eliteLevels);
            $this->assertContains($expectedPhone, $elitePhones);
        }
    }

    public function eliteLevelPhoneProvider(): array
    {
        return [
            ['', '2-33-66-000', null],
            ['Level 1', '2-33-66', 'Elite Level 1'],
            ['Level 2', '2-33-10', 'Elite Level 2'],
        ];
    }
}
