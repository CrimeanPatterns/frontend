<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\SpenAnalysisController;

use AwardWallet\MainBundle\Entity\ProviderMileValue;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\ClickhouseFactory;
use Codeception\Util\Locator;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

/**
 * @group frontend-functional
 */
class MerchantOfferByNameCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testOffer(\TestSymfonyGuy $I)
    {
        $date = time();
        $userId = $I->createAwUser();
        $providerId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD]);
        $accountId = $I->createAwAccount($userId, $providerId, "test");
        $creditCardId = $I->createAwCreditCard($providerId);
        $shoppingCategoryId = $I->haveInDatabase("ShoppingCategory", [
            "Name" => "Shopping category " . bin2hex(random_bytes(5)),
            "MatchingOrder" => 1,
        ]);
        $merchantId = $I->createAwMerchant(["ShoppingCategoryID" => $shoppingCategoryId]);
        $merchantName = $I->grabFromDatabase("Merchant", "DisplayName", ["MerchantID" => $merchantId]);
        $subAccountId = $I->createAwSubAccount($accountId, ["CreditCardID" => $creditCardId]);

        $offerProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD]);
        $offerAccountId = $I->createAwAccount($userId, $offerProviderId, "test");
        $offerCreditCardId = $I->createAwCreditCard($offerProviderId);
        $offerCreditCardName = $I->grabFromDatabase("CreditCard", "Name", ["CreditCardID" => $offerCreditCardId]);
        $offerSubAccountId = $I->createAwSubAccount($offerAccountId, ["CreditCardID" => $offerCreditCardId]);

        $withCobrandProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD]);
        $cobrandProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_AIRLINE]);
        $withCobrandCreditCardId = $I->createAwCreditCard($withCobrandProviderId, ["CobrandProviderID" => $cobrandProviderId]);
        $withCobrandCreditCardName = $I->grabFromDatabase("CreditCard", "Name", ["CreditCardID" => $withCobrandCreditCardId]);
        $I->haveInDatabase("CreditCardShoppingCategoryGroup", [
            "CreditCardID" => $withCobrandCreditCardId,
            "Multiplier" => 8.0,
            "Description" => "Test 8x on all",
        ]);
        $I->fillAwMileValue($cobrandProviderId);

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

        /** @var Connection $clickhouse */
        $clickhouse = $I->grabService(ClickhouseFactory::class)->getConnection();
        $I->assertEquals(1, $clickhouse->insert("SubAccount", [
            "SubAccountID" => $offerSubAccountId,
            "AccountID" => $offerAccountId,
            "CreditCardID" => $offerCreditCardId,
            "Code" => bin2hex(random_bytes(5)),
        ]));

        for ($n = 0; $n < SpentAnalysisService::MIN_MERCHANT_TRANSACTIONS; $n++) {
            $I->assertEquals(1, $clickhouse->insert("AccountHistory", [
                "UUID" => Uuid::uuid4()->toString(),
                "AccountID" => $offerAccountId,
                "SubAccountID" => $offerSubAccountId,
                "CreditCardID" => $offerCreditCardId,
                "PostingDate" => date("Y-m-d", strtotime("yesterday", $date)),
                "Miles" => 2000,
                'Amount' => 100,
                "Multiplier" => 2,
                "MerchantID" => $merchantId,
            ]));
        }

        $I->haveInDatabase("ProviderMileValue", [
            "ProviderID" => $offerProviderId,
            "AvgPointValue" => 3.0,
            "Status" => ProviderMileValue::STATUS_ENABLED,
        ]);

        $I->switchToUser($userId);
        $I->followRedirects(false);
        $I->amOnPage("/spend-analysis/merchant-offer-by-name/" . urlencode($merchantName) . '_' . $merchantId, [
            "source" => "transaction-history&mid=web",
            "uuid" => $uuid,
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContains('According to our records these cards have been recently earning more than 1x at <strong>' . $merchantName . '</strong>');
        $I->seeResponseContains($offerCreditCardName);
        $I->see("6¢ per dollar", Locator::firstElement("//div[contains(@class, 'container__col-card') and .//text()[contains(., '$offerCreditCardName')]]//..//span[@class='curr-info']"));
        $I->see($withCobrandCreditCardName);
        $I->see("Test 8x on all");
        // 7.2 = 8.0 (multiplier) x 0.9 (mile value)
        $I->see("7.2¢ per dollar", Locator::firstElement("//div[contains(@class, 'container__col-card') and .//text()[contains(., '$withCobrandCreditCardName')]]//..//span[@class='curr-info']"));
    }
}
