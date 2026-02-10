<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\SpenAnalysisController;

use Ramsey\Uuid\Uuid;

/**
 * @group frontend-functional
 */
class TransactionOfferCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testOffer(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $providerId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD]);
        $accountId = $I->createAwAccount($userId, $providerId, "test");
        $creditCardId = $I->createAwCreditCard($providerId);
        $merchantId = $I->createAwMerchant();
        $subAccountId = $I->createAwSubAccount($accountId, ["CreditCardID" => $creditCardId]);

        $uuid = Uuid::uuid4()->toString();
        $I->haveInDatabase("AccountHistory", [
            "UUID" => $uuid,
            "AccountID" => $accountId,
            "SubAccountID" => $subAccountId,
            "MerchantID" => $merchantId,
            "PostingDate" => "2000-01-01 00:00:00",
            "Description" => "Existing tx",
            "Miles" => 1000,
            "Position" => 1,
            'Amount' => 100,
        ]);
        $I->switchToUser($userId);
        $I->followRedirects(false);
        $I->sendPost("/spend-analysis/transaction-offer", [
            "source" => "transaction-history&mid=web",
            "uuid" => $uuid,
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContains('You spent <strong>$100.00</strong> at <strong>Existing tx</strong> and earned <strong>1,000</strong> points');
    }
}
