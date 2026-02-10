<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Service\Billing\EmailPlusOnlyOptionsListener
 * @group frontend-unit
 */
class EmailPlusOnlyOptionsListenerCest
{
    private const CUSTOM_FIELD_VALUES = [
        'EmailExpiration' => 0,
        'EmailRewards' => REWARDS_NOTIFICATION_WEEK,
        'EmailNewPlans' => 0,
        'EmailPlansChanges' => 0,
        'CheckinReminder' => 0,
        'EmailProductUpdates' => 0,
        'EmailOffers' => 0,
        'EmailNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_DAY,
        'EmailInviteeReg' => 0,
        'EmailFamilyMemberAlert' => 0,
    ];

    private const STANDARD_FIELD_VALUES = [
        'EmailExpiration' => 1,
        'EmailRewards' => REWARDS_NOTIFICATION_DAY,
        'EmailNewPlans' => 1,
        'EmailPlansChanges' => 1,
        'CheckinReminder' => 1,
        'EmailProductUpdates' => 1,
        'EmailOffers' => 1,
        'EmailNewBlogPosts' => NotificationModel::BLOGPOST_NEW_NOTIFICATION_DAY, // should be kept
        'EmailInviteeReg' => 1,
        'EmailFamilyMemberAlert' => 1,
    ];

    public function testEnableOptionsOnDowngrade(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, array_merge(['AccountLevel' => ACCOUNT_LEVEL_AWPLUS], self::CUSTOM_FIELD_VALUES));
        /** @var PlusManager $plusManager */
        $plusManager = $I->grabService(PlusManager::class);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        $user = $em->find(Usr::class, $userId);
        $plusManager->checkExpirationAndDowngrade($user);

        $I->seeInDatabase('Usr', array_merge(
            [
                'UserID' => $userId,
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
            ],
            self::STANDARD_FIELD_VALUES
        ));
    }

    public function testFreeToPlus(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, array_merge(['AccountLevel' => ACCOUNT_LEVEL_FREE], self::CUSTOM_FIELD_VALUES));
        $I->expect("should not reset custom values when going from free to plus");
        /** @var Manager $cartManager */
        $cartManager = $I->grabService(Manager::class);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        /** @var Usr $user */
        $user = $em->find(Usr::class, $userId);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $user->getAccountLevel());
        $cartManager->setUser($user);
        $cartManager->createNewCart();
        $cartManager->addSubscription(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR);
        $cartManager->markAsPayed();
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $user->getAccountLevel());
        $I->seeInDatabase("Usr", array_merge(["UserID" => $userId], self::CUSTOM_FIELD_VALUES));
    }

    public function testRestoreFields(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, array_merge(['AccountLevel' => ACCOUNT_LEVEL_AWPLUS], self::CUSTOM_FIELD_VALUES));
        /** @var PlusManager $plusManager */
        $plusManager = $I->grabService(PlusManager::class);
        /** @var EntityManagerInterface $em */
        $em = $I->grabService(EntityManagerInterface::class);
        $user = $em->find(Usr::class, $userId);
        $I->assertNull($user->getFieldsBeforeDowngrade());
        $plusManager->checkExpirationAndDowngrade($user);
        $I->assertNotNull($user->getFieldsBeforeDowngrade());
        $I->seeInDatabase('Usr', array_merge(
            [
                'UserID' => $userId,
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
            ],
            self::STANDARD_FIELD_VALUES
        ));

        /** @var Manager $cartManager */
        $cartManager = $I->grabService(Manager::class);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $user->getAccountLevel());
        $cartManager->setUser($user);
        $cartManager->createNewCart();
        $cartManager->addSubscription(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR);
        $cartManager->markAsPayed();
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $user->getAccountLevel());
        $I->seeInDatabase("Usr", array_merge(["UserID" => $userId], self::CUSTOM_FIELD_VALUES));
    }
}
