<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Service\Billing\StripeOffSessionCharger;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Stub;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Service\CustomerService;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\PaymentMethodService;
use Stripe\Service\SetupIntentService;
use Stripe\SetupIntent;
use Stripe\StripeClient;
use Stripe\StripeObject;

class StripeIntent extends Module
{
    private $lastCartId;
    private $lastSetupIntentId;
    private $lastPaymentIntentId;
    private $paymentMethodId;
    private SymfonyTestHelper $symfonyTestHelper;

    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        parent::__construct($moduleContainer, $config);

        $this->symfonyTestHelper = $this->getModule('SymfonyTestHelper');
    }

    public function _before(TestInterface $test)
    {
        $this->lastSetupIntentId = null;
        $this->lastCartId = null;
    }

    public function prepareStripeIntentMocks(?string $customerName = null, bool $setupIntent = true)
    {
        if ($customerName === null) {
            $customerName = "Ragnar Petrovich";
        }

        $stripe = $this->symfonyTestHelper->stubMake(StripeClient::class);

        $stripe->customers = $this->symfonyTestHelper->stubMake(CustomerService::class, [
            'create' => function (array $params) use ($customerName) {
                $this->assertEquals($customerName, $params['name']);

                $result = new Customer('cu_123');
                $result->name = $params['name'];
                $result->metadata = $params['metadata'];
                $result->email = $params['email'];

                return $result;
            },
            'retrieve' => function (string $id) use ($customerName) {
                $result = new Customer($id);
                $result->name = $customerName;

                return $result;
            },
        ]);

        $this->lastCartId = null;
        $this->paymentMethodId = 'pm_123';

        if ($setupIntent) {
            $this->lastSetupIntentId = 'si_' . bin2hex(random_bytes(4));
            $stripe->setupIntents = $this->symfonyTestHelper->stubMake(SetupIntentService::class, [
                'create' => Stub::atLeastOnce(function (array $options) {
                    $result = new SetupIntent($this->lastSetupIntentId);
                    $result->client_secret = "cs_123";
                    $result->customer = "cu_123";
                    $this->lastCartId = $options['metadata']['cart_id'];

                    return $result;
                }),
                'retrieve' => function (string $id) {
                    $this->assertEquals($this->lastSetupIntentId, $id);
                    $result = new SetupIntent($this->lastSetupIntentId);
                    $result->status = SetupIntent::STATUS_SUCCEEDED;
                    $result->payment_method = $this->paymentMethodId;
                    $result->customer = 'cu_123';

                    return $result;
                },
            ]);
        } else {
            $this->lastPaymentIntentId = 'pi_' . bin2hex(random_bytes(4));
            $stripe->paymentIntents = $this->symfonyTestHelper->stubMake(PaymentIntentService::class, [
                'create' => Stub::atLeastOnce(function (array $options) {
                    $result = new PaymentIntent($this->lastPaymentIntentId);
                    $result->client_secret = "cs_123";
                    $result->customer = "cu_123";
                    $this->lastCartId = $options['metadata']['cart_id'];

                    return $result;
                }),
                'retrieve' => function (string $id) {
                    $this->assertEquals($this->lastPaymentIntentId, $id);
                    $result = new PaymentIntent($this->lastPaymentIntentId);
                    $result->status = PaymentIntent::STATUS_SUCCEEDED;
                    $result->payment_method = $this->paymentMethodId;
                    $result->customer = 'cu_123';

                    return $result;
                },
            ]);
        }

        $stripe->paymentMethods = $this->symfonyTestHelper->stubMake(PaymentMethodService::class, [
            'retrieve' => function (string $id) {
                $this->assertEquals($this->paymentMethodId, $id);
                $result = new PaymentMethod($id);
                $result->card = new StripeObject();
                $result->card->brand = 'visa';
                $result->card->last4 = '4242';
                $result->created = time();
                $result->customer = 'cu_123';
                $result->type = 'card';

                return $result;
            },
        ]);

        /** @var SymfonyTestHelper $helper */
        $symfonyHelper = $this->getModule('SymfonyTestHelper');
        $symfonyHelper->mockService(StripeClient::class, $stripe);
    }

    public function payWithStripeIntent(int $expectedUserId, string $expectedUserEmail, bool $selectPaymentType, ?float $immediateUsd, $scheduledUsd = null, ?bool $seeEmail = true, ?callable $extraChecks = null): int
    {
        if ($selectPaymentType) {
            $this->prepareStripeIntentMocks(null, $scheduledUsd !== null);
        }

        /** @var SymfonyTestHelper $helper */
        $symfonyHelper = $this->getModule('SymfonyTestHelper');

        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');

        $charger = $this->symfonyTestHelper->stubMake(StripeOffSessionCharger::class, [
            'charge' => $immediateUsd && $scheduledUsd ? Stub::once(function (string $customer, string $paymentMethod, float $amount, int $aCartId, ?int $userId) use ($expectedUserId, $immediateUsd, $symfony) {
                $symfony->assertEquals("cu_123", $customer);
                $symfony->assertEquals("pm_123", $paymentMethod);
                $symfony->assertEquals($immediateUsd, $amount);
                $symfony->assertEquals($this->lastCartId, $aCartId);
                $symfony->assertEquals($expectedUserId, $userId);

                return "pi_123";
            }) : Stub::never(),
        ]);

        $symfonyHelper->mockService(StripeOffSessionCharger::class, $charger);

        if ($selectPaymentType) {
            $symfony->see("Select Payment Type");
            $symfony->submitForm("//form", [
                'select_payment_type' => [
                    '_token' => $symfony->grabAttributeFrom("//input[@name='select_payment_type[_token]']", "value"),
                    'type' => Cart::PAYMENTTYPE_STRIPE_INTENT,
                ],
            ]);
        }
        $symfony->seeInSource("const stripe = Stripe");

        if ($extraChecks !== null) {
            call_user_func($extraChecks);
        }

        $symfony->assertNotNull($scheduledUsd ? $this->lastSetupIntentId : $this->lastPaymentIntentId);
        $symfony->amOnRoute("aw_cart_stripe_returned", $scheduledUsd ? ["setup_intent" => $this->lastSetupIntentId] : ["payment_intent" => $this->lastPaymentIntentId]);
        $symfony->assertNotNull($this->lastCartId);

        if ($immediateUsd) {
            $symfony->see("You have successfully paid $" . number_format($immediateUsd, 2) . " for order #{$this->lastCartId}");
        } else {
            $symfony->see("You have scheduled to pay $" . number_format($scheduledUsd, 2));
            $symfony->see("order #{$this->lastCartId}");
        }

        /** @var Mail $mail */
        $mail = $this->getModule('Mail');

        if ($seeEmail !== null) {
            if ($seeEmail) {
                $mail->seeEmailTo($expectedUserEmail, "Order ID: {$this->lastCartId}", null, 30);
            } else {
                $mail->dontSeeEmailTo($expectedUserEmail, "Order ID: {$this->lastCartId}", null, 30);
            }
        }

        $symfonyHelper->verifyMocks();

        return $this->lastCartId;
    }
}
