<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Globals\Cart\Manager
 */
class ManagerCest
{
    public function testPrepaidAndScheduledSubscription(\TestSymfonyGuy $I)
    {
        $login = "tu" . bin2hex(random_bytes(8));
        $userId = $I->createAwUser($login);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        /** @var Usr $user */
        $user = $I->grabService(UsrRepository::class)->find($userId);
        /** @var Manager $manager */
        $manager = $I->grabService(Manager::class);
        $manager->setUser($user);
        $manager->createNewCart();
        $prepaid = new AwPlusPrepaid();
        $prepaid->setCnt(2);
        $manager->getCart()->addItem($prepaid);
        $manager->addAwSubscriptionItem($manager->getCart(), new \DateTime("+2 year"));
        $manager->markAsPayed();
        $I->grabService(EntityManagerInterface::class)->refresh($user);
        $I->assertEquals(SubscriptionPeriod::DURATION_TO_DAYS[AwPlusSubscription::DURATION], $user->getSubscriptionPeriod());
    }

    public function testScheduledSubscriptionAndPrepaid(\TestSymfonyGuy $I)
    {
        $login = "tu" . bin2hex(random_bytes(8));
        $userId = $I->createAwUser($login);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        /** @var Usr $user */
        $user = $I->grabService(UsrRepository::class)->find($userId);
        /** @var Manager $manager */
        $manager = $I->grabService(Manager::class);
        $manager->setUser($user);
        $manager->createNewCart();
        $manager->addAwSubscriptionItem($manager->getCart(), new \DateTime("+2 year"));
        $prepaid = new AwPlusPrepaid();
        $prepaid->setCnt(2);
        $manager->getCart()->addItem($prepaid);
        $manager->markAsPayed();
        $I->grabService(EntityManagerInterface::class)->refresh($user);
        $I->assertEquals(SubscriptionPeriod::DURATION_TO_DAYS[AwPlusSubscription::DURATION], $user->getSubscriptionPeriod());
    }
}
