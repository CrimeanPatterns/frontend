<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\CreditCards\Commands\AnalyzeMerchantStatsCommand;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group frontend-functional
 */
class MerchantLookupControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testMerchantWithGroup(\TestSymfonyGuy $I)
    {
        $I->markTestSkipped('test failing on low-speed machines');
        $shoppingCategoryGroupName = "Shopping category " . bin2hex(random_bytes(5));
        $shoppingCategoryId = $I->haveInDatabase("ShoppingCategory", [
            "Name" => $shoppingCategoryGroupName,
            "MatchingOrder" => 1,
        ]);
        $merchantPatternId = $I->createAwMerchantPattern();
        $merchantId = $I->createAwMerchant([
            "ShoppingCategoryID" => $shoppingCategoryId,
            "MerchantPatternID" => $merchantPatternId,
        ]);
        $merchantName = $I->grabFromDatabase("Merchant", "DisplayName", ["MerchantID" => $merchantId]);
        $providerId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD]);
        $creditCardId = $I->createAwCreditCard($providerId);
        $creditCardName = $I->grabFromDatabase("CreditCard", "Name", ["CreditCardID" => $creditCardId]);
        $merchantGroupId = $I->haveInDatabase("MerchantGroup", ["Name" => "Merchant group " . bin2hex(random_bytes(4))]);
        $I->haveInDatabase("MerchantPatternGroup", [
            "MerchantGroupID" => $merchantGroupId,
            "MerchantPatternID" => $merchantPatternId,
        ]);
        $description = "8x on merch group $merchantGroupId";
        $I->haveInDatabase("CreditCardMerchantGroup", [
            "CreditCardID" => $creditCardId,
            "MerchantGroupID" => $merchantGroupId,
            "Multiplier" => 8.0,
            "Description" => $description,
        ]);
        $userId = $I->createAwUser();
        $accountId = $I->createAwAccount($userId, $providerId, "test");
        $subAccountId = $I->createAwSubAccount($accountId, ["CreditCardID" => $creditCardId]);
        $date = time();

        for ($n = 0; $n < SpentAnalysisService::MIN_MULTIPLIER_TRANSACTIONS; $n++) {
            $I->haveInDatabase("AccountHistory", [
                "UUID" => Uuid::uuid4()->toString(),
                "AccountID" => $accountId,
                "SubAccountID" => $subAccountId,
                "MerchantID" => $merchantId,
                "PostingDate" => date("Y", $date) . "-01-01 00:00:00",
                "Description" => "Existing tx",
                "Miles" => 800,
                "Position" => 1,
                'Amount' => 100,
                'Multiplier' => 8.0,
                'ShoppingCategoryID' => $shoppingCategoryId,
            ]);
        }
        $I->runSymfonyConsoleCommand(AnalyzeMerchantStatsCommand::$defaultName, [
            "--merchantId" => [$merchantId],
        ]);

        $I->amOnPage("/merchants/" . urlencode($merchantName) . '_' . $merchantId);
        $I->seeResponseCodeIsSuccessful();

        if (!preg_match("#angular\.module\('merchantLookupApp'\)\.constant\('merchantOffer', '([^']+)'#ims", $I->grabResponse(), $matches)) {
            $I->fail("Can't see merchantOffer data");
        }

        $data = html_entity_decode($matches[1]);

        $I->assertStringContainsString($merchantName, $data);
        $I->assertStringContainsString($shoppingCategoryGroupName, $data);
        $I->assertStringContainsString($creditCardName, $data);
        $I->assertStringContainsString($description, $data);
        file_put_contents(codecept_log_dir() . "testMerchantWithGroup.html", $data);

        $crawler = new Crawler($data);

        foreach (['cards-transactions', 'cards-excluded'] as $cssClass) {
            $cardRow = $crawler->filterXPath(
                "//div[contains(@class, '{$cssClass}') and div[@class = 'container__title']]//div[contains(@class, 'container__caption') and .//text()[contains(., '{$creditCardName}')]]"
            );
            $I->assertEquals(0, $cardRow->count());
        }

        $cardRow = $crawler->filterXPath(
            "//div[contains(@class, 'cards-list') and div[@class = 'container__title']]//div[contains(@class, 'container__caption') and .//text()[contains(., '{$creditCardName}')]]"
        );
        $I->assertEquals(1, $cardRow->count());
    }
}
