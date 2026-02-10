<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\OfferManager;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group frontend-functional
 */
class OfferCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var \TestSymfonyGuy
     */
    protected $I;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->I = $I;
        $this->userId = $I->createAwUser($login = 'testOffer-' . $I->grabRandomString(), Aw::DEFAULT_PASSWORD, [
            'FirstName' => 'First',
            'LastName' => 'Last',
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'InBeta' => 1,
            'BetaApproved' => 1,
            'GoogleAuthSecret' => 'some',
            'GoogleAuthRecoveryCode' => 'some',
        ], true, true);

        $I->amOnPage('/' . "?_switch_user=" . $login);
    }

    public function _after()
    {
        $this->I = null;
        $this->userId = null;
    }

    public function testNoOffers(\TestSymfonyGuy $I)
    {
        $this->createOffer('testshouldbealwaysshown', null, 10000000);
        $this->createOffer('testshouldbenevershown', null, 10000000);

        $I->sendGET("/offer/find");
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals("none");
    }

    public function testOfferShouldBeNeverShown(\TestSymfonyGuy $I)
    {
        [$_, $offerUserId] = $this->createOfferWithUserLink('testshouldbenevershown', null, 10000000);

        $I->sendGET("/offer/find");
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals("none");

        $I->sendGET("/manager/offer/preview/{$offerUserId}?preview=yes");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('User is not eligble for this offer at the moment!');

        $I->sendGET("/offer/show/{$offerUserId}");
        // redirected to referer
        $I->seeCurrentUrlEquals("/manager/offer/preview/{$offerUserId}?preview=yes");
    }

    public function testOfferShouldBeAlwaysShown(\TestSymfonyGuy $I)
    {
        [$_, $offerUserId] = $this->createOfferWithUserLink('testshouldbealwaysshown', null, 10000000);

        $I->sendGET("/offer/find");
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals("redirect {$offerUserId}");
    }

    public function testOfferNotShownForPlusWithSplashAdsDisabled(\TestSymfonyGuy $I)
    {
        /** @var OfferManager $offerManager */
        $offerManager = $I->grabService(OfferManager::class);
        [$_, $offerUserId] = $this->createOfferWithUserLink('testshouldbealwaysshown', null, 10000000);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        /** @var Usr $user */
        $user = $em->find(Usr::class, $this->userId);
        $user->setSplashAdsDisabled(true);
        $em->flush();
        $offer = $offerManager->checkUserOffers($user, new Request());
        $I->assertFalse($offer);
    }

    public function testOfferShownForFreeWithSplashAdsDisabled(\TestSymfonyGuy $I)
    {
        /** @var OfferManager $offerManager */
        $offerManager = $I->grabService(OfferManager::class);
        [$_, $offerUserId] = $this->createOfferWithUserLink('testshouldbealwaysshown', null, 10000000);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        /** @var Usr $user */
        $user = $em->find(Usr::class, $this->userId);
        $user->setSplashAdsDisabled(true);
        $user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $em->flush();
        $offer = $offerManager->checkUserOffers($user, new Request());
        $I->assertEquals($offerUserId, $offer);
    }

    public function testOfferCycling(\TestSymfonyGuy $I)
    {
        $this->createOfferWithUserLink('testshouldbenevershown', null, 10000001);
        [$_, $offerUserId] = $this->createOfferWithUserLink('testshouldbealwaysshown', null, 10000000);

        $I->sendGET("/offer/find");
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals("redirect {$offerUserId}");
    }

    /**
     * @param string $code
     * @param string|null $name
     * @return array
     */
    protected function createOfferWithUserLink($code, $name, $priority)
    {
        $offerId = $this->createOffer($code, $name, $priority);
        $offerUserId = $this->I->haveInDatabase('OfferUser', [
            'OfferID' => $offerId,
            'UserID' => $this->userId,
            'CreationDate' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        return [$offerId, $offerUserId];
    }

    /**
     * @param string $code
     * @param string $name
     * @param int $priority
     * @return int
     */
    protected function createOffer($code, $name, $priority)
    {
        $this->I->executeQuery("DELETE FROM Offer WHERE Code = '{$code}'");

        return $this->I->haveInDatabase(
            'Offer',
            [
                'Enabled' => 1,
                'Name' => StringHandler::isEmpty($name) ? $code : $name,
                'Code' => $code,
                'CreationDate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'MaxShows' => 5,
                'RemindMeDays' => 1,
                'Priority' => $priority,
                'ApplyURL' => 'http://some.link',
            ]
        );
    }
}
