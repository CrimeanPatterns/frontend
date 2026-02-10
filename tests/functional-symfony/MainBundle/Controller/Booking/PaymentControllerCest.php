<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Booking;

use AwardWallet\MainBundle\Controller\Booking\PaymentController;
use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group billing
 * @coversDefaultClass PaymentController
 */
class PaymentControllerCest extends BaseTraitCest
{
    use StaffUser;
    use LoggedIn;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->em = $I->grabService('doctrine.orm.entity_manager');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = $this->em = null;
        parent::_after($I);
    }

    /**
     * @dataProvider paymentTypeByBookerDataProvider
     */
    public function testPaymentTypeByBookerData(\TestSymfonyGuy $I, Example $example)
    {
        $bookerId = call_user_func($example['createBookerFunction'], $I);
        /** @var BookingRequestManager $abRequestManager */
        $abRequestManager = $I->grabService(BookingRequestManager::class);
        /** @var AbRequest $abRequest */
        $abRequest = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find(
            $requestId = $I->createAbRequest(['UserID' => $this->user->getId(), 'BookerUserID' => $bookerId])
        );
        $abRequestManager->addInvoice(
            (new AbInvoice())
                ->addItem(
                    (new BookingServiceFee())
                        ->setDescription('Test Booking Fee')
                        ->setPrice(100)
                        ->setQuantity(1)
                        ->setDiscount(0)
                ),
            $abRequest
        );
        $I->sendPOST($this->router->generate("aw_booking_payment_by_cc", ["id" => $requestId]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "ok"]);
        $I->assertEquals($example['expectedPaymentType'], $I->grabFromDatabase("Cart", "PaymentType", ["UserID" => $this->user->getId()]));
    }

    private function paymentTypeByBookerDataProvider()
    {
        return [
            [
                'createBookerFunction' => function (\TestSymfonyGuy $I) {
                    return $I->createBusinessUserWithBookerInfo(null, [], ['PaypalClientID' => 'notEmpty', 'PayPalPassword' => 'notEmpty', 'FromEmail' => 'some@email.com']);
                },
                'expectedPaymentType' => PAYMENTTYPE_CREDITCARD,
            ],
            [
                'createBookerFunction' => function (\TestSymfonyGuy $I) {
                    return Aw::BOOKER_ID;
                },
                'expectedPaymentType' => PAYMENTTYPE_STRIPE_INTENT,
            ],
        ];
    }
}
