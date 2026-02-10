<?php

namespace AwardWallet\Tests\Playwright\Tests\Timeline;

use AwardWallet\Tests\Modules\DbBuilder\User;

class SummaryCest
{
    public function createTimeline(\CodeGuy $I)
    {
        $user = new User();
        $I->makeUser($user);

        file_put_contents(str_replace(".Cest.php", ".data.json", __FILE__), json_encode([
            "user_id" => $user->getId(),
            "login" => $user->getFields()["Login"],
        ], JSON_PRETTY_PRINT));
    }
}
