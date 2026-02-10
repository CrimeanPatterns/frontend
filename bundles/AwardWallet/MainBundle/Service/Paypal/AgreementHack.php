<?php

namespace AwardWallet\MainBundle\Service\Paypal;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use PayPal\Api\Agreement;
use PayPal\Validation\ArgumentValidator;

/**
 * @NoDI
 */
class AgreementHack extends Agreement
{
    public static function get($agreementId, $apiContext = null, $restCall = null)
    {
        ArgumentValidator::validate($agreementId, 'agreementId');
        $payLoad = "";
        $json = self::executeCall(
            "/v1/payments/billing-agreements/$agreementId",
            "GET",
            $payLoad,
            null,
            $apiContext,
            $restCall
        );
        $ret = new Agreement();

        // hack
        $data = json_decode($json, true);

        if (isset($data['plan']['merchant_preferences'])) {
            foreach ($data['plan']['merchant_preferences'] as $key => $value) {
                if (substr($key, -4) === '_url' && $value === null) {
                    $data['plan']['merchant_preferences'][$key] = 'http://paypal.com/fakeUrl';
                }
            }
            $json = json_encode($data);
        }

        $ret->fromJson($json);

        return $ret;
    }
}
