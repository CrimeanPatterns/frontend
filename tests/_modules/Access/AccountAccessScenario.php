<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\StringUtils;

class AccountAccessScenario extends Scenario
{
    /**
     * @var int
     */
    public $accountId;

    /**
     * @var int
     */
    public $subAccountId;

    /**
     * @var string
     */
    public $login;

    protected $victimStaff = true;

    public function create(\TestSymfonyGuy $I)
    {
        parent::create($I);
        $this->login = StringUtils::getRandomCode(10);
        $this->accountId = $I->createAwAccount($this->victimId, 'testprovider', $this->login, "some_account_password");
        $this->subAccountId = $I->createAwSubAccount($this->accountId);

        if ($this->shared) {
            if (empty($this->victimConnectionId)) {
                throw new \InvalidArgumentException("shared implies victimToAttacker connection");
            }
            $I->haveInDatabase('AccountShare', ['AccountID' => $this->accountId, 'UserAgentID' => $this->victimConnectionId]);
        }
    }

    public static function dataProvider()
    {
        // return arrays because codeception does not allow us to return objects from dataProvider
        return [
            ['scenario' => new static(Action::REDIRECT_TO_LOGIN, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), null, true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_WRITE), false)],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_WRITE), new Connection(true, Useragent::ACCESS_READ_NUMBER), true)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(true, Useragent::ACCESS_READ_NUMBER), new Connection(true, Useragent::ACCESS_WRITE), true)],
        ];
    }
}
