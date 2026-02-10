<?php

use Codeception\Scenario;

/**
 * @group frontend-acceptance
 */
class ListCest
{
    public function _before(WebGuy $I)
    {
    }

    public function filters(WebGuy $I)
    {
        $I->wantTo("Account list filters");
        $I->comment('initialize');
        $I->amOnPage($I->grabService('router')->generate(AccountListPage::$router, ['_switch_user' => CommonUser::$admin_login]));
        $I->waitForElementVisible(AccountListPage::$is_successInitialized, 25);
        $accountRows = $I->grabNumberOfElements(AccountListPage::$selector_accountRow);
        $kindRows = $I->grabNumberOfElements(AccountListPage::$selector_kindRow);
        $kindUserRows = $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow);
        $I->assertGreaterThan(0, $accountRows);
        $I->assertGreaterThan(0, $kindRows);
        $I->assertGreaterThan(0, $kindUserRows);

        $I->executeJS(AccountListPage::$js_showMenu);
        $I->executeJS("$('#tipjsOverlay').click()");

        $I->amGoingTo('filter by archived');
        $I->click(AccountListPage::$selector_archivedTab);
        $archivedAccountRows = $I->grabTextFrom(AccountListPage::$selector_archivedTab . '/span[contains(@class, "amount")]');
        $I->assertEquals($archivedAccountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));

        $I->amGoingTo('back to all');
        $I->click(AccountListPage::$selector_allTab);
        $I->waitForElementNotVisible(AccountListPage::$is_successFiltered, 3);
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));

        $I->amGoingTo('filter by user agent');
        $I->click(AccountListPage::$selector_firstAgent);
        $I->wait(3);
        $I->assertLessThan($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow));

        $I->amGoingTo('back to all');
        $I->click(AccountListPage::$selector_allAgent);
        $I->wait(3);
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));
        $I->assertEquals($kindUserRows, $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow));

        $I->amGoingTo('filter only changed');
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterChangeWeek, 3);
        $I->click(AccountListPage::$selector_filterChangeWeek);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterChangeWeek, 3);
        $I->waitForElementVisible(AccountListPage::$is_successChangeWeek, 3);
        $I->assertLessThan($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));

        $I->amGoingTo('back to all');
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterFullList, 3);
        $I->click(AccountListPage::$selector_filterFullList);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterFullList, 3);
        $I->wait(3);
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));

        $I->amGoingTo('search');
        $I->fillField(AccountListPage::$selector_searchField, 'test');
        $I->wait(3);
        $I->assertLessThan($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));

        $I->amGoingTo('clear search');
        $I->click(AccountListPage::$selector_searchClear);
        $I->wait(3);
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));
    }

    public function options(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("Account list options");

        $I->comment('initialize');
        $I->amOnPage($I->grabService('router')->generate(AccountListPage::$router, ['_switch_user' => CommonUser::$admin_login]));
        $I->waitForElementVisible(AccountListPage::$is_successInitialized, 25);
        $accountRows = $I->grabNumberOfElements(AccountListPage::$selector_accountRow);
        $kindRows = $I->grabNumberOfElements(AccountListPage::$selector_kindRow);
        $kindUserRows = $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow);
        $errorRows = $I->grabNumberOfElements(AccountListPage::$selector_errorRow);

        $I->comment('grouped by default');
        $I->amGoingTo("ungroup list");
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterUngroup, 3);
        $I->click(AccountListPage::$selector_filterUngroup);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterUngroup, 3);
        $I->wait(3);
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_kindRow));
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow));

        $I->comment('show errors by default');
        $I->amGoingTo("hide errors");
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterHideErrors, 3);
        $I->click(AccountListPage::$selector_filterHideErrors);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterHideErrors, 3);
        $I->wait(3);
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_errorRow));

        $I->amGoingTo("check session storage");
        $I->comment('reload without hash parameters');
        $I->amOnPage($I->grabService('router')->generate(AccountListPage::$router));
        $I->waitForElementVisible(AccountListPage::$is_successInitialized, 25);
        $I->comment('list ungrouped');
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_kindRow));
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow));
        $I->comment('errors hidden');
        $I->assertEquals(0, $I->grabNumberOfElements(AccountListPage::$selector_errorRow));

        $I->amGoingTo("group list");
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterGroup, 3);
        $I->click(AccountListPage::$selector_filterGroup);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterGroup, 3);
        $I->wait(3);
        $I->assertEquals($accountRows, $I->grabNumberOfElements(AccountListPage::$selector_accountRow));
        $I->assertEquals($kindRows, $I->grabNumberOfElements(AccountListPage::$selector_kindRow));
        $I->assertEquals($kindUserRows, $I->grabNumberOfElements(AccountListPage::$selector_kindUserRow));

        $I->amGoingTo("show errors");
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterShowErrors, 3);
        $I->click(AccountListPage::$selector_filterShowErrors);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterShowErrors, 3);
        $I->assertEquals($errorRows, $I->grabNumberOfElements(AccountListPage::$selector_errorRow));
    }

    public function orders(WebGuy $I, Scenario $scenario)
    {
        $orders = AccountListPage::$data_orders;

        $I->wantTo("Account list sort orders");

        $I->comment('initialize');
        $I->amOnPage($I->grabService('router')->generate(AccountListPage::$router, ['_switch_user' => CommonUser::$admin_login]));
        $I->waitForElementVisible(AccountListPage::$is_successInitialized, 25);

        $I->comment('test only ungroup list');
        $I->click(AccountListPage::$selector_filterMenu);
        $I->waitForElementVisible(AccountListPage::$selector_filterUngroup, 3);
        $I->click(AccountListPage::$selector_filterUngroup);
        $I->waitForElementNotVisible(AccountListPage::$selector_filterUngroup, 3);
        $I->wait(3);

        $I->comment('list always ordered');
        $firstRow = $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id');
        $lastRow = $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id');
        $currentOrder = $I->grabAttributeFrom(AccountListPage::$selector_orderCurrent, 'class');
        $I->assertTrue(in_array($currentOrder, $orders), $currentOrder);
        $I->seeElement(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection));
        $currentDirection = $I->grabAttributeFrom(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection), 'class');

        $I->assertTrue(in_array(preg_replace('/\s*ng-\S*\s*/i', '', $currentDirection), AccountListPage::$data_directions), $currentDirection);

        $I->amGoingTo("reverse order");
        $I->click(str_replace('%field%', $currentOrder, AccountListPage::$selector_order));
        $I->assertNotEquals($firstRow, $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id'));
        $I->assertNotEquals($lastRow, $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id'));
        $I->assertNotEquals($currentDirection, $I->grabAttributeFrom(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection), 'class'));

        $I->amGoingTo("back to normal order");
        $I->click(str_replace('%field%', $currentOrder, AccountListPage::$selector_order));
        $I->wait(3);
        $I->assertEquals($firstRow, $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id'));
        $I->assertEquals($lastRow, $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id'));
        $I->assertEquals($currentDirection, $I->grabAttributeFrom(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection), 'class'));

        $I->amGoingTo("next order");
        $orders = array_filter($orders, function ($i) use ($currentOrder) {return $i != $currentOrder; });
        $I->assertGreaterThan(0, count($orders));

        $prevOrder = $currentOrder;
        $currentOrder = reset($orders);
        $I->dontSeeElement(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection));

        $I->click(str_replace('%field%', $currentOrder, AccountListPage::$selector_order));
        $I->wait(3);
        $I->seeElement(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection));
        $I->dontSeeElement(str_replace('%field%', $prevOrder, AccountListPage::$selector_orderDirection));
        $firstRow = $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id');
        $lastRow = $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id');
        $currentDirection = $I->grabAttributeFrom(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection), 'class');

        $I->amGoingTo("reverse order");
        $I->click(str_replace('%field%', $currentOrder, AccountListPage::$selector_order));
        $I->wait(3);
        $I->assertNotEquals($firstRow, $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id'));
        $I->assertNotEquals($lastRow, $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id'));
        $I->assertNotEquals($currentDirection, $I->grabAttributeFrom(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection), 'class'));

        $I->amGoingTo("check session storage");

        $I->comment('store state');
        $firstRow = $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id');
        $lastRow = $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id');
        $currentDirection = $I->grabAttributeFrom(str_replace('%field%', $currentOrder, AccountListPage::$selector_orderDirection), 'class');

        $I->comment('reload without hash parameters');
        $I->amOnPage($I->grabService('router')->generate(AccountListPage::$router));
        $I->waitForElementVisible(AccountListPage::$is_successInitialized, 25);
        $I->comment('ungroup state save too');
        $I->wait(3);

        $order = $I->grabAttributeFrom(AccountListPage::$selector_orderCurrent, 'class');
        $I->assertEquals($currentOrder, $order);
        $I->assertEquals($firstRow, $I->grabAttributeFrom(AccountListPage::$selector_firstRow, 'id'));
        $I->assertEquals($lastRow, $I->grabAttributeFrom(AccountListPage::$selector_lastRow, 'id'));
        $I->assertEquals($currentDirection, $I->grabAttributeFrom(str_replace('%field%', $order, AccountListPage::$selector_orderDirection), 'class'));
    }
}
