<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;

class ItineraryFileAccessScenario extends TimelineAccessScenario
{
    public function getAttackerId(): ?int
    {
        return $this->attackerId;
    }

    public static function dataProvider(): array
    {
        return [
            ['scenario' => new static(Action::REDIRECT_TO_LOGIN, false)],
            [
                'scenario' => new static(Action::FORBIDDEN, true,
                    new Connection(false, Useragent::ACCESS_WRITE),
                    null,
                    true
                ),
            ],
            [
                'scenario' => new static(Action::FORBIDDEN, true,
                    new Connection(true, Useragent::ACCESS_WRITE),
                    null,
                    true
                ),
            ],
            [
                'scenario' => new static(Action::FORBIDDEN, true,
                    new Connection(true, Useragent::ACCESS_WRITE),
                    new Connection(true, Useragent::ACCESS_WRITE),
                    false
                ),
            ],
            [
                'scenario' => new static(Action::FORBIDDEN, true,
                    new Connection(true, Useragent::ACCESS_READ_NUMBER),
                    new Connection(true, Useragent::ACCESS_WRITE),
                    true
                ),
            ],
            [
                'scenario' => new static(Action::FORBIDDEN, true,
                    new Connection(true, Useragent::ACCESS_WRITE),
                    new Connection(true, Useragent::ACCESS_WRITE),
                    true, false
                ),
            ],
            [
                'scenario' => new static(Action::ALLOWED, true,
                    new Connection(true, Useragent::ACCESS_WRITE),
                    new Connection(true, Useragent::ACCESS_WRITE),
                    true, true
                ),
            ],
        ];
    }
}
