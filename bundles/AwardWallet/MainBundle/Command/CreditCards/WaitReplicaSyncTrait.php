<?php
/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 15/12/2017
 * Time: 16:41.
 */

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Globals\StringUtils;

trait WaitReplicaSyncTrait
{
    public function waitReplicaSync()
    {
        $randomStr = "sync_" . StringUtils::getRandomCode(10);
        $this->mainConnection->executeUpdate(
            "INSERT INTO Param(Name, Val) VALUES(?, ?)",
            [$randomStr, $randomStr]
        );

        $synced = false;
        $start = time();

        while ($start + 60 > time()) {
            $val = $this->replicaConnection->executeQuery("SELECT Val FROM Param WHERE Name = ?", [$randomStr])->fetch();
            $synced = !empty($val);

            if ($synced) {
                break;
            }
            sleep(1);
        }

        $this->mainConnection->executeUpdate("DELETE FROM Param WHERE Name = ?", [$randomStr]);

        if (!$synced) {
            throw new \Exception("Data sync to replica connection has broken");
        }
    }
}
