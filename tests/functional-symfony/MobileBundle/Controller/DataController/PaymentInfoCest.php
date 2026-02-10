<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\DataController;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * TODO: remove?
 */
class PaymentInfoCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    /**
     * @var Billing
     */
    private $billing;
    /**
     * @var Manager
     */
    private $cartManager;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->cartManager = $I->grabService('aw.manager.cart');
        $this->em = $I->grabService('doctrine.orm.default_entity_manager');
        $this->cartManager->setUser($this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->user->getUserid()));
        $this->billing = new Billing(
            $this->em,
            $this->cartManager,
            (new Prophet())->prophesize(LoggerInterface::class)->reveal(),
            $I->grabService('aw.email.mailer'),
            (new Prophet())->prophesize(RecurringManager::class)->reveal()
        );
        $I->setMobileVersion('4.0.0');
    }

    public function testNewlyRegisteredUserWithTrialPeriodWith315Version(\TestSymfonyGuy $I)
    {
        $I->setMobileVersion('3.15.0');
        $this->giveTrial();
        $this->assertPaymentInfo(
            $I,
            ['Free' => false, 'AccountLevel' => 'AwardWallet Trial'],
            ['text' => 'Trial'],
            []
        );
    }

    public function testNewlyRegisteredUserWithTrialPeriod(\TestSymfonyGuy $I)
    {
        $this->giveTrial();
        $this->assertPaymentInfo(
            $I,
            ['Free' => false, 'AccountLevel' => 'AwardWallet Trial'],
            ['text' => 'Trial', 'formLink' => '#upgrade', 'subHint' => $this->period('+3 month')],
            []
        );
    }

    public function testUserWithExpiredTrial(\TestSymfonyGuy $I)
    {
        $this->assertPaymentInfo(
            $I,
            ['Free' => true, 'AccountLevel' => 'Regular'],
            ['text' => 'Regular', 'formLink' => '#upgrade'],
            []
        );
    }

    public function testUserWithActiveSubscriptionWithinTrialPeriod(\TestSymfonyGuy $I)
    {
        $this->giveTrial('-1 week');
        $this->subscribe();
        $this->assertPaymentInfo(
            $I,
            ['Free' => false, 'AccountLevel' => 'AwardWallet Plus'],
            ['text' => 'AwardWallet Plus', 'subHint' => $this->period('+1 year 3 month -5 day')],
            ['formLink' => '#cancelSubscription', 'name' => 'Recurring payment', 'text' => 'App Store (iOS)']
        );
    }

    public function testUserWithActiveSubscriptionAfterTrialPeriod(\TestSymfonyGuy $I)
    {
        $this->giveTrial('-6 month');
        $this->subscribe();
        $this->assertPaymentInfo(
            $I,
            ['Free' => false, 'AccountLevel' => 'AwardWallet Plus'],
            ['text' => 'AwardWallet Plus', 'subHint' => $this->period('+1 year')],
            ['formLink' => '#cancelSubscription', 'name' => 'Recurring payment', 'text' => 'App Store (iOS)']
        );
    }

    public function testUserWithUnactiveSubscription(\TestSymfonyGuy $I)
    {
        $this->giveTrial('-2 year');
        $this->subscribe('-1 year -2 month');
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->user->getUserid());
        $user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->em->flush($user);
        $this->assertPaymentInfo(
            $I,
            ['Free' => true, 'AccountLevel' => 'Regular'],
            ['text' => 'Regular', 'formLink' => '#upgrade'],
            []
        );
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->billing = null;

        parent::_after($I);
    }

    protected function period(string $time = 'now'): string
    {
        $dateTime = new \DateTime($time);

        return 'on ' . $dateTime->format('n/j/y');
    }

    protected function giveTrial(string $time = 'now')
    {
        $cart = $this->cartManager->createNewCart();
        $cart->addItem(new AwPlusTrial());
        $this->cartManager->markAsPayed($cart, null, new \DateTime($time));
        $this->em->flush();
    }

    protected function assertPaymentInfo(\TestSymfonyGuy $I, array $profileExpected, array $payBlockExpected, array $recurringBlockExpected)
    {
        $I->sendGET('/m/api/data');
        $data = $I->grabDataFromJsonResponse();
        $profileActual = $data['profile'];
        $payBlockActual = it($profileActual['overview'])
            ->filterByContainsArray(['name' => 'Account Type'])
            ->first() ?? [];
        $recurringBlockActual = it($profileActual['overview'])
            ->filterByContainsArray(['name' => 'Recurring payment'])
            ->first() ?? [];

        $I->assertArrayContainsArray($profileExpected, $profileActual);
        $I->assertArrayContainsArray($payBlockExpected, $payBlockActual);
        $I->assertArrayContainsArray($recurringBlockExpected, $recurringBlockActual);
    }

    protected function subscribe(string $time = 'now')
    {
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->user->getUserid());
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            AwPlus::class,
            $user,
            Cart::PAYMENTTYPE_APPSTORE,
            StringHandler::getRandomCode(20),
            $payDate = new \DateTime($time)
        );
        $purchase->setPurchaseToken(StringHandler::getRandomCode(20));
        $purchase->setRecurring(true);

        $this->billing->tryUpgrade($purchase);
        $this->em->flush();
    }
}
