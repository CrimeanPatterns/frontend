<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use Codeception\Module;
use Codeception\Util\Stub;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileResponseDetailsType;
use PayPal\EBLBaseComponents\GetExpressCheckoutDetailsResponseDetailsType;
use PayPal\EBLBaseComponents\PayerInfoType;
use PayPal\EBLBaseComponents\PersonNameType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileResponseType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsResponseType;
use PayPal\PayPalAPI\SetExpressCheckoutResponseType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

class PayPal extends Module
{
    public function preparePayPalMocks(int $trialAmount, float $amount)
    {
        /** @var SymfonyTestHelper $symfonyTestHelper */
        $symfonyTestHelper = $this->getModule('SymfonyTestHelper');
        $paypalService = $symfonyTestHelper->stubMake(PayPalAPIInterfaceServiceService::class, [
            'SetExpressCheckout' => function () {
                return new SetExpressCheckoutResponseType();
            },
            'GetExpressCheckoutDetails' => function () {
                $result = new GetExpressCheckoutDetailsResponseType();
                $payerInfo = new PayerInfoType();
                $payerName = new PersonNameType();
                $payerName->FirstName = "John";
                $payerName->LastName = "Smith";
                $payerInfo->PayerName = $payerName;
                $result->GetExpressCheckoutDetailsResponseDetails = new GetExpressCheckoutDetailsResponseDetailsType();
                $result->GetExpressCheckoutDetailsResponseDetails->PayerInfo = $payerInfo;
                $result->Ack = 'Success';

                return $result;
            },
            'CreateRecurringPaymentsProfile' => function (CreateRecurringPaymentsProfileReq $req) use ($symfonyTestHelper, $amount, $trialAmount) {
                $symfonyTestHelper->assertEquals($amount, $req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->ScheduleDetails->PaymentPeriod->Amount->value);

                if (!empty($trialAmount)) {
                    $symfonyTestHelper->assertEquals($trialAmount, $req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->ScheduleDetails->TrialPeriod->Amount->value);
                } else {
                    $symfonyTestHelper->assertEmpty($req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->ScheduleDetails->TrialPeriod);
                }
                $result = new CreateRecurringPaymentsProfileResponseType();
                $result->CreateRecurringPaymentsProfileResponseDetails = new CreateRecurringPaymentsProfileResponseDetailsType();
                $result->CreateRecurringPaymentsProfileResponseDetails->ProfileID = 'someProfileId';
                $result->Ack = 'Success';

                return $result;
            },
            //                    'DoExpressCheckoutPayment' => Stub::exactly(1, function(){
            //                        $result = new \DoExpressCheckoutPaymentResponseType();
            //                        $result->DoExpressCheckoutPaymentResponseDetails = new \DoExpressCheckoutPaymentResponseDetailsType();
            //                        $paymentInfo = new \PaymentInfoType();
            //                        $paymentInfo->TransactionID = 'someTranId';
            //                        $result->DoExpressCheckoutPaymentResponseDetails->PaymentInfo = [ $paymentInfo ];
            //                        return $result;
            //                    })
        ]);

        $paypal = $symfonyTestHelper->stubMake(PaypalSoapApi::class, [
            'getPaypalService' => $paypalService,
            'getPaypalServiceForCart' => Stub::atLeastOnce(function ($debug = false, Cart $cart) use ($paypalService) {
                // why mocks does not work inside ?
                return $paypalService;
            }),
        ]);
        $symfonyTestHelper->mockService(PaypalSoapApi::class, $paypal);
    }

    public function processPayPal(): int
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $router = $symfony->grabService('router');

        $symfony->see("You are being redirected to PayPal.com");
        $symfony->sendAjaxPostRequest($router->generate("aw_cart_paypal_process"));
        $symfony->seeInSource('"status":"ok"');

        $symfony->amOnPage($router->generate("aw_cart_paypal_accept", ['token' => 'sometoken', 'PayerID' => 'somepayer']));
        $symfony->see("do you wish to pay");
        $symfony->sendAjaxPostRequest($router->generate("aw_cart_paypal_accept", ['token' => 'sometoken', 'PayerID' => 'somepayer']));
        $symfony->seeInSource('"status":"ok"');

        $symfony->sendAjaxGetRequest($router->generate("aw_cart_paypal_process"));
        $symfony->seeInSource('"status":"ok"');

        $source = $symfony->grabPageSource();

        if (preg_match('#"url":"\\\/cart\\\/complete\\\/(\d+)"#ims', $source, $matches)) {
            return $matches[1];
        }

        throw new \Exception("Invalid paypal response: $source");
    }
}
