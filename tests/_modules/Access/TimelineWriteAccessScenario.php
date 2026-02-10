<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;

class TimelineWriteAccessScenario extends TimelineAccessScenario
{
    public static function dataProvider()
    {
        // return arrays because codeception does not allow us to return objects from dataProvider
        return [
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true, true, self::TIMELINE_FAMILY_MEMBER)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false, true, self::TIMELINE_FAMILY_MEMBER)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false, true)],
            ['scenario' => new static(Action::REDIRECT_TO_LOGIN, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), null, true)],
        ];
    }
}
