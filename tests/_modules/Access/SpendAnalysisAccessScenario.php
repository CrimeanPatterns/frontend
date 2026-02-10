<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\StringUtils;

class SpendAnalysisAccessScenario extends Scenario
{
    /** @var int */
    public $businessAttackerId;

    /** @var bool */
    public $authorizedBusiness;

    public function __construct(
        $expectedAction,
        $authorized,
        ?Connection $victimToAttackerConnection = null,
        ?Connection $attackerToVictimConnection = null,
        $shared = false,
        $authorizedBusiness = true
    ) {
        parent::__construct($expectedAction, $authorized, $victimToAttackerConnection, $attackerToVictimConnection, $shared);
        $this->authorizedBusiness = $authorizedBusiness;
    }

    public function create(\TestSymfonyGuy $I)
    {
        parent::create($I);

        if ($this->authorizedBusiness) {
            $login = "hckr_business_" . StringUtils::getRandomCode(10);
            $this->businessAttackerId = $I->createAwUser($login, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]);
            $I->connectUserWithBusiness($this->attackerId, $this->businessAttackerId, ACCESS_ADMIN);

            if (isset($this->victimToAttackerConnection)) {
                $this->victimConnectionId = $I->createConnection($this->victimId, $this->businessAttackerId, $this->victimToAttackerConnection->approved, null, ["AccessLevel" => $this->victimToAttackerConnection->accessLevel]);
            }
        }
    }

    public static function dataProvider(): array
    {
        return [
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_READ_ALL), null, false, false)],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_READ_ALL))],
            ['scenario' => new static(Action::FORBIDDEN, true, new Connection(false, Useragent::ACCESS_READ_ALL), new Connection(true, Useragent::ACCESS_READ_ALL))],
            ['scenario' => new static(Action::FORBIDDEN, true, null, new Connection(true, Useragent::ACCESS_READ_ALL))],
            ['scenario' => new static(Action::ALLOWED, true, new Connection(true, Useragent::ACCESS_READ_ALL))],
        ];
    }
}
