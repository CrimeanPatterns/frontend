<?php

namespace AwardWallet\Tests\Unit\src\service\AccountHandler\AccountAuditor;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class NextEliteLevelTest extends BaseUserTest
{
    private $accountInfo;

    public function _before()
    {
        parent::_before();

        $this->accountInfo = (new \Account($this->aw->createAwAccount($this->user->getUserid(), $this->aw->createAwProvider(), 'test')))
            ->getAccountInfo();
        $this->db->haveInDatabase('ProviderProperty', [
            'ProviderID' => $this->accountInfo['ProviderID'],
            'Name' => 'Status',
            'Code' => 'Status',
            'SortIndex' => 1,
            'Kind' => PROPERTY_KIND_STATUS,
        ]);
    }

    public function _after()
    {
        parent::_after();

        $this->accountInfo = null;
    }

    public function testEliteLevelNotByDefaultShouldNotBeNextLevel()
    {
        $this->addEliteLevel('Silver', 1, 1, ['SILVER']);
        $this->addEliteLevel('Gold', 2, 0);

        $props = [
            'Status' => 'SILVER',
        ];
        \AccountAuditor::getNextEliteLevel($this->accountInfo, $props);
        $this->assertCount(1, $props);
        $this->assertArrayHasKey('Status', $props);
    }

    public function testEliteLevelByDefaultShouldBeNextLevel()
    {
        $this->addEliteLevel('Silver', 1, 1);
        $this->addEliteLevel('Gold', 2, 1, ['Gold']);
        $this->addEliteLevel('Platinum', 3, 1);

        $props = [
            'Status' => 'Gold',
        ];
        \AccountAuditor::getNextEliteLevel($this->accountInfo, $props);
        $this->assertCount(2, $props);
        $this->assertArrayHasKey('Status', $props);
        $this->assertArrayHasKey('NextEliteLevel', $props);
        $this->assertEquals('Platinum', $props['NextEliteLevel']);
    }

    public function testNoNextEliteLevel()
    {
        $this->addEliteLevel('Silver', 1, 1);
        $this->addEliteLevel('Gold', 2, 1, ['Gold']);

        $props = [
            'Status' => 'Gold',
        ];
        \AccountAuditor::getNextEliteLevel($this->accountInfo, $props);
        $this->assertCount(1, $props);
        $this->assertArrayHasKey('Status', $props);
        $this->assertEquals('Gold', $props['Status']);
    }

    private function addEliteLevel(string $name, int $rank, int $byDefault = 1, array $aliases = [])
    {
        $elId = $this->db->haveInDatabase('EliteLevel', [
            'ProviderID' => $this->accountInfo['ProviderID'],
            'Name' => $name,
            'Rank' => $rank,
            'ByDefault' => $byDefault,
        ]);

        foreach ($aliases as $alias) {
            $this->db->haveInDatabase('TextEliteLevel', [
                'EliteLevelID' => $elId,
                'ValueText' => $alias,
            ]);
        }
    }
}
