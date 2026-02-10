<?php

namespace AwardWallet\Tests\Unit;

class SchemaManagerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var \TSchemaManager
     */
    private $manager;
    private $userId;

    public function _before(\CodeGuy $I)
    {
        $this->manager = new \TSchemaManager();
        $this->userId = $I->createAwUser(null, null, [], true, true);
    }

    public function testCompare(\CodeGuy $I)
    {
        $accountId1 = $I->createAwAccount($this->userId, "testprovider", "future.trip.random.seats");
        $accountId2 = $I->createAwAccount($this->userId, "testprovider", "future.trip.random.seats");

        $I->checkAccount($accountId1, true);
        $snapshot1 = $this->loadAccountSnapshot($I, $accountId1);

        $I->checkAccount($accountId2, true);
        $snapshot2 = $this->loadAccountSnapshot($I, $accountId2);

        $diff = $this->arrayRecursiveDiff($snapshot1, $snapshot2);
        $I->assertEmpty($diff);
    }

    public function _after()
    {
        $this->manager = null;
        $this->userId = null;
    }

    private function loadAccountSnapshot(\CodeGuy $I, $accountId)
    {
        $rows = [
            ['Table' => 'Account', 'ID' => $accountId],
        ];
        $row = $I->query("select * from Account where AccountID = :accountId", ['accountId' => $accountId])->fetch(\PDO::FETCH_ASSOC);
        $rows = array_merge($rows, $this->manager->ChildRows("Account", $row));
        $rows = array_reverse($rows);
        $this->manager->loadRows($rows);
        $rows = array_map(function (array $row) { return $this->filterRow($row['Values']); }, $rows);

        return $rows;
    }

    private function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = [];

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);

                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = "$mValue -> {$aArray2[$mKey]}";
                    }
                }
            } else {
                $aReturn[$mKey] = "+" . $mValue;
            }
        }

        return $aReturn;
    }

    private function filterRow(array $values)
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (preg_match('#\w+(ID|Date)$#ms', $key)) {
                continue;
            }

            if (in_array($key, ["BrowserState"])) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
