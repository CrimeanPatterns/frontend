<?php

namespace AwardWallet\Tests\Unit\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Command\CreditCards\MerchantDisplayNameCommand;
use AwardWallet\MainBundle\Service\CreditCards\MerchantDisplayNameGenerator;
use Ramsey\Uuid\Uuid;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Command\CreditCards\MerchantDisplayNameCommand
 */
class MerchantDisplayNameCommandCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const LAST_TX_DESCRIPTION = "Some last tx name";

    public function notCustomName(\CodeGuy $I)
    {
        $merchantId = $this->createMerchantWithTransaction($I);

        $I->runSymfonyConsoleCommand(MerchantDisplayNameCommand::$defaultName, [
            "--merchantId" => $merchantId,
        ]);

        $I->assertEquals(MerchantDisplayNameGenerator::create(self::LAST_TX_DESCRIPTION), $I->grabFromDatabase("Merchant", "DisplayName", ["MerchantID" => $merchantId]));
    }

    public function customName(\CodeGuy $I)
    {
        $merchantId = $this->createMerchantWithTransaction($I);
        $I->updateInDatabase("Merchant", ["IsCustomDisplayName" => 1], ["MerchantID" => $merchantId]);
        $oldMerchantName = $I->grabFromDatabase("Merchant", "DisplayName", ["MerchantID" => $merchantId]);

        $I->runSymfonyConsoleCommand(MerchantDisplayNameCommand::$defaultName, [
            "--merchantId" => $merchantId,
        ]);

        $I->assertEquals($oldMerchantName, $I->grabFromDatabase("Merchant", "DisplayName", ["MerchantID" => $merchantId]));
    }

    /**
     * @return int - merchantId
     */
    private function createMerchantWithTransaction(\CodeGuy $I): int
    {
        $merchantId = $I->createAwMerchant();
        $providerId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD]);
        $creditCardId = $I->createAwCreditCard($providerId);
        $userId = $I->createAwUser();
        $accountId = $I->createAwAccount($userId, $providerId, "test");
        $subAccountId = $I->createAwSubAccount($accountId, ["CreditCardID" => $creditCardId]);
        $date = time();
        $I->haveInDatabase("AccountHistory", [
            "UUID" => Uuid::uuid4()->toString(),
            "AccountID" => $accountId,
            "SubAccountID" => $subAccountId,
            "MerchantID" => $merchantId,
            "PostingDate" => date("Y", $date) . "-01-01 00:00:00",
            "Description" => self::LAST_TX_DESCRIPTION,
            "Miles" => 800,
            "Position" => 1,
            'Amount' => 100,
            'Multiplier' => 8.0,
        ]);

        return $merchantId;
    }
}
