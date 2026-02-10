<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\UserCreditCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\CreditCards\Advertise;
use AwardWallet\MainBundle\Service\CreditCards\Advertise\Ad;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class AdvertiseTest extends BaseUserTest
{
    /** @var Usr */
    protected $user;
    /** @var Advertise */
    private $advertiseService;

    /** @var Account */
    private $account;

    /** @var CreditCard */
    private $creditCard;

    public function _before(): void
    {
        parent::_before();

        $this->advertiseService = $this->container->get(Advertise::class);

        $this->user = $this->em->getRepository(Usr::class)->find(
            $this->aw->createAwUser(null, null, ['CountryID' => Country::UNITED_STATES], true)
        );

        $this->account = $this->em->getRepository(Account::class)->find(
            $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'creditCard', Aw::DEFAULT_PASSWORD)
        );

        $this->db->executeQuery('UPDATE CreditCard SET SortIndex = 4 WHERE SortIndex IS NULL');
        $this->creditCard = $this->em->getRepository(CreditCard::class)->find(
            $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => Aw::TEST_PROVIDER_ID,
                'Name' => 'Test Card',
                'IsBusiness' => 0,
                'MatchingOrder' => 1,
                'CardFullName' => 'Test Provider Card',
                'VisibleOnLanding' => 1,
                'VisibleInList' => 1,
                'ExcludeCardsId' => null,
                'DirectClickURL' => 'http://test-card.com',
                'Text' => 'description',
                'PictureVer' => '123',
                'PictureExt' => 'jpg',
                'SortIndex' => 1,
            ])
        );
    }

    public function _after(): void
    {
        $this->em->remove($this->creditCard);
        $this->em->flush();

        unset($this->account, $this->user, $this->advertiseService, $this->creditCard);

        parent::_after();
    }

    public function testByKind(): void
    {
        $adsList = $this->advertiseService->getListByUser($this->user);
        $this->assertArrayHasKey((string) PROVIDER_KIND_AIRLINE, $adsList);
        $this->assertEquals($adsList[PROVIDER_KIND_AIRLINE]->title, $this->creditCard->getCardFullName());
    }

    public function testNotVisibleInList(): void
    {
        $this->creditCard->setVisibleInList(false);
        $this->em->flush();

        $adsList = $this->advertiseService->getListByUser($this->user);

        if (array_key_exists(PROVIDER_KIND_AIRLINE, $adsList)) {
            $this->assertNotEquals($adsList[PROVIDER_KIND_AIRLINE]->title, $this->creditCard->getCardFullName());
        }
    }

    public function testExcludeCards(): void
    {
        /** @var Provider $providerCard */
        $providerCard = $this->em->getRepository(Provider::class)->find(
            $this->aw->createAwProvider(null, null, ['Kind' => PROVIDER_KIND_CREDITCARD])
        );
        $accountCard = $this->em->getRepository(Account::class)->find(
            $this->aw->createAwAccount($this->user->getUserid(), $providerCard->getProviderid(), 'creditCard', Aw::DEFAULT_PASSWORD)
        );

        $creditCard2Group = $this->em->getRepository(CreditCard::class)->find(
            $card2Id = $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => $providerCard->getProviderid(),
                'Name' => 'Test Card 2',
                'IsBusiness' => 0,
                'MatchingOrder' => 1,
                'CardFullName' => 'Test Provider Card 2',
                'VisibleOnLanding' => 1,
                'VisibleInList' => 1,
                'ExcludeCardsId' => 1,
                'DirectClickURL' => 'http://test-card2.com',
                'Text' => 'description 2',
                'PictureVer' => '123',
                'PictureExt' => 'jpg',
                'SortIndex' => 2,
            ])
        );

        $this->invalidateCreditCardCache();
        $adsList = $this->advertiseService->getListByUser($this->user);
        $titleList = array_column($adsList, 'title');
        $this->assertContains(\mb_strtolower($creditCard2Group->getCardFullName()), it($titleList)->mapToLower('UTF-8')->toArray(), '');

        $this->creditCard->setExcludeCardsId([$card2Id]);
        $this->em->persist($this->creditCard);
        $this->em->flush();

        $detectedCard = $this->em->getRepository(UserCreditCard::class)->find(
            $this->db->haveInDatabase('UserCreditCard', [
                'UserID' => $this->user->getUserid(),
                'CreditCardID' => $this->creditCard->getId(),
                'DetectedViaBank' => 1,
            ])
        );

        $this->invalidateCreditCardCache();
        $adsList = $this->advertiseService->getListByUser($this->user);
        $titleList = it(array_column($adsList, 'title'))->mapToLower('UTF-8')->toArray();
        $this->assertNotContains(\mb_strtolower($this->creditCard->getCardFullName()), $titleList, '');
        $this->assertNotContains(mb_strtolower($creditCard2Group->getCardFullName()), $titleList, '');

        $this->em->remove($detectedCard);
        $this->em->flush();
    }

    public function testMultipleKinds(): void
    {
        /** @var Provider $providerCard */
        $providerCard = $this->em->getRepository(Provider::class)->find(
            $this->aw->createAwProvider(null, null, ['Kind' => PROVIDER_KIND_CREDITCARD])
        );
        /** @var Provider $providerAirline */
        $providerAirline = $this->em->getRepository(Provider::class)->find(
            $this->aw->createAwProvider(null, null, ['Kind' => PROVIDER_KIND_AIRLINE])
        );

        $accountCard = $this->em->getRepository(Account::class)->find(
            $this->aw->createAwAccount($this->user->getUserid(), $providerCard->getProviderid(), 'creditCard2', Aw::DEFAULT_PASSWORD)
        );
        $accountAir = $this->em->getRepository(Account::class)->find(
            $this->aw->createAwAccount($this->user->getUserid(), $providerAirline->getProviderid(), 'creditCard3', Aw::DEFAULT_PASSWORD)
        );

        $creditCardAir = $this->em->getRepository(CreditCard::class)->find(
            $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => $providerAirline->getProviderid(),
                'Name' => 'Test Airline Card',
                'IsBusiness' => 0,
                'MatchingOrder' => 1,
                'CardFullName' => 'Test Airline Provider Card',
                'VisibleOnLanding' => 1,
                'VisibleInList' => 1,
                'ExcludeCardsId' => null,
                'DirectClickURL' => 'http://test-airline-card.com',
                'Text' => 'description',
                'PictureVer' => '123',
                'PictureExt' => 'jpg',
                'SortIndex' => 2,
            ])
        );

        $this->invalidateCreditCardCache();
        $adsList = $this->advertiseService->getListByUser($this->user);

        $this->assertArrayHasKey((string) PROVIDER_KIND_AIRLINE, $adsList);
        $this->assertArrayHasKey((string) PROVIDER_KIND_CREDITCARD, $adsList);

        $this->em->flush();
    }

    public function testBusiness(): void
    {
        /** @var Provider $provider */
        $provider = $this->em->getRepository(Provider::class)->find(
            $this->aw->createAwProvider()
        );
        $provider2 = $this->em->getRepository(Provider::class)->find(
            $this->aw->createAwProvider()
        );

        $businessCard = $this->em->getRepository(CreditCard::class)->find(
            $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => $provider->getProviderid(),
                'Name' => 'Test Business Card 2',
                'IsBusiness' => 1,
                'MatchingOrder' => 1,
                'CardFullName' => 'Test Provider Business Card',
                'VisibleOnLanding' => 1,
                'VisibleInList' => 1,
                'DirectClickURL' => 'http://test-card2.com',
                'Text' => 'description 2',
                'PictureVer' => '123',
                'PictureExt' => 'jpg',
                'SortIndex' => 1,
            ])
        );
        $businessCardShownToUser = $this->em->getRepository(CreditCard::class)->find(
            $this->db->haveInDatabase('CreditCard', [
                'ProviderID' => $provider2->getProviderid(),
                'Name' => 'Test Business Card 3',
                'IsBusiness' => 1,
                'MatchingOrder' => 1,
                'CardFullName' => 'Test Provider Business Card 3',
                'VisibleOnLanding' => 1,
                'VisibleInList' => 1,
                'DirectClickURL' => 'http://test-card3.com',
                'Text' => 'description 3',
                'PictureVer' => '123',
                'PictureExt' => 'jpg',
                'SortIndex' => 0,
            ])
        );
        $detectedCard = $this->em->getRepository(UserCreditCard::class)->find(
            $this->db->haveInDatabase('UserCreditCard', [
                'UserID' => $this->user->getUserid(),
                'CreditCardID' => $businessCard->getId(),
                'DetectedViaBank' => 1,
            ])
        );

        foreach ([PROVIDER_KIND_HOTEL, PROVIDER_KIND_CAR_RENTAL, PROVIDER_KIND_TRAIN, PROVIDER_KIND_OTHER, PROVIDER_KIND_CREDITCARD, PROVIDER_KIND_SHOPPING, PROVIDER_KIND_DINING, PROVIDER_KIND_SURVEY] as $kindId) {
            $providerId = $this->aw->createAwProvider(null, null, ['Kind' => $kindId]);
            $this->aw->createAwAccount($this->user->getUserid(), $providerId, 'creditCard' . StringUtils::getRandomCode(12), Aw::DEFAULT_PASSWORD);
        }

        $this->invalidateCreditCardCache();
        $adsList = $this->advertiseService->getListByUser($this->user);
        $foundBusinessCard = false;

        /** @var Ad $ad */
        foreach ($adsList as $ad) {
            if (false !== strpos($ad->title, 'Business')) {
                $foundBusinessCard = true;
            }
        }
        $this->assertTrue($foundBusinessCard);
    }

    public function testCountry(): void
    {
        $user = $this->em->getRepository(Usr::class)->find(
            $this->aw->createAwUser(null, null, ['CountryID' => Country::RUSSIA], true)
        );
        $adsList = $this->advertiseService->getListByUser($user);
        $this->assertEmpty($adsList);
    }

    private function invalidateCreditCardCache(): void
    {
        $this->container->get(CacheManager::class)->invalidateGlobalTags([Tags::getCreditCardAdKey($this->user->getUserid())]);
    }
}
