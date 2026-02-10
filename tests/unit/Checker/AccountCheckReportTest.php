<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseTest;

class AccountCheckReportTest extends BaseTest
{
    public function testTagsInErrorMessage()
    {
        $report = new \AccountCheckReport();
        $userId = $this->aw->createAwUser();
        $accountId = $this->aw->createAwAccount($userId, "testprovider", "no-matter-what");
        $report->account = new \Account($accountId);
        $report->errorMessage = 'Some link <a href="https://ya.ru/" onclick="test" target="_blank">click here</a>, and some <svg onload="alert(1)">tag</svg>';
        $report->filter();
        $this->assertEquals('Some link <a href="https://ya.ru/" target="_blank" rel="noreferrer">click here</a>, and some tag', $report->errorMessage);
    }
}
