<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;

class AccountLocationAccessScenario extends AccountAccessScenario
{
    /**
     * @var int
     */
    public $accountLocationId;

    /**
     * @var int
     */
    public $subAccountLocationId;

    public function create(\TestSymfonyGuy $I)
    {
        parent::create($I);

        $this->accountLocationId = $I->haveInDatabase("Location", [
            "Name" => "Test Location",
            "Lat" => 10,
            "Lng" => 20,
            "AccountID" => $this->accountId,
        ]);
        $this->subAccountLocationId = $I->haveInDatabase("Location", [
            "Name" => "Test Location 2",
            "Lat" => 30,
            "Lng" => 40,
            "SubAccountID" => $this->subAccountId,
        ]);
    }

    public static function editDataProvider()
    {
        return [
            ['scenario' => new static(Action::FORBIDDEN, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_READ_NUMBER), new Connection(true, Useragent::ACCESS_WRITE), true)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true)],
        ];
    }

    public static function deleteDataProvider()
    {
        return [
            ['scenario' => new static(Action::FORBIDDEN, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_READ_NUMBER), new Connection(true, Useragent::ACCESS_WRITE), true)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), true)],
        ];
    }
}
