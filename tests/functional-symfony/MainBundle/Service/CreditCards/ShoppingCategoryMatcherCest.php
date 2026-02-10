<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;

/**
 * @group frontend-functional
 */
class ShoppingCategoryMatcherCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function matchGroupByPattern(\TestSymfonyGuy $I)
    {
        $groupPattern = bin2hex(random_bytes(10));
        $categoryName = "cat_" . $groupPattern;
        $groupName = "grp_" . $groupPattern;

        $groupId = $I->haveInDatabase("ShoppingCategoryGroup", ["Name" => $groupName, "Patterns" => $groupPattern]);

        /** @var ShoppingCategoryMatcher $matcher */
        $matcher = $I->grabService(ShoppingCategoryMatcher::class);
        $categoryId = $matcher->identify($categoryName, Provider::TEST_PROVIDER_ID);

        $I->assertNotNull($categoryId);
        $group = $I->query("select Name, ShoppingCategoryGroupID from ShoppingCategory where ShoppingCategoryID = ?", [$categoryId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals($groupId, $group['ShoppingCategoryGroupID']);
        $I->assertEquals($categoryName, $group['Name']);
    }
}
