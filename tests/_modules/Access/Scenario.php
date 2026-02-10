<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Globals\StringUtils;

class Scenario
{
    /**
     * see constants in Action class.
     *
     * @var string
     */
    public $expectedAction;

    /**
     * @var bool
     */
    public $authorized = false;

    /**
     * @var int
     */
    public $victimId;

    /**
     * @var int
     */
    public $victimConnectionId;

    /**
     * @var int
     */
    public $attackerConnectionId;

    /**
     * @var Connection
     */
    protected $victimToAttackerConnection;

    /**
     * @var Connection
     */
    protected $attackerToVictimConnection;

    /**
     * @var bool
     */
    protected $shared = false;

    /**
     * @var bool
     */
    protected $victimStaff = false;

    /**
     * @var int
     */
    protected $attackerId;

    public function __construct($expectedAction, $authorized, ?Connection $victimToAttackerConnection = null, ?Connection $attackerToVictimConnection = null, $shared = false)
    {
        $this->expectedAction = $expectedAction;
        $this->authorized = $authorized;
        $this->victimToAttackerConnection = $victimToAttackerConnection;
        $this->attackerToVictimConnection = $attackerToVictimConnection;
        $this->shared = $shared;
    }

    public function setExpectedAction($expectedAction)
    {
        $this->expectedAction = $expectedAction;

        return $this;
    }

    /**
     * @param bool $authorized
     * @return Scenario
     */
    public function setAuthorized($authorized)
    {
        $this->authorized = $authorized;

        return $this;
    }

    /**
     * @return Scenario
     */
    public function setVictimToAttackerConnection(Connection $victimToAttackerConnection)
    {
        $this->victimToAttackerConnection = $victimToAttackerConnection;

        return $this;
    }

    /**
     * @return Scenario
     */
    public function setAttackerToVictimConnection(Connection $attackerToVictimConnection)
    {
        $this->attackerToVictimConnection = $attackerToVictimConnection;

        return $this;
    }

    /**
     * @param bool $shared
     * @return Scenario
     */
    public function setShared($shared)
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * @param bool $victimStaff
     * @return Scenario
     */
    public function setVictimStaff($victimStaff)
    {
        $this->victimStaff = $victimStaff;

        return $this;
    }

    public function create(\TestSymfonyGuy $I)
    {
        $this->victimId = $I->createAwUser(null, null, [], $this->victimStaff);

        if ($this->authorized) {
            $attackerLogin = "hckr_" . StringUtils::getRandomCode(10);
            $this->attackerId = $I->createAwUser($attackerLogin, null, [], true);
            $I->sendGET("/m/api/login_status", ["_switch_user" => $attackerLogin]);
            $I->seeResponseCodeIs(200);
        }

        if (isset($this->victimToAttackerConnection)) {
            if (empty($this->attackerId)) {
                throw new \InvalidArgumentException("connection implies authorized");
            }
            $this->victimConnectionId = $I->createConnection($this->victimId, $this->attackerId, $this->victimToAttackerConnection->approved, null, ["AccessLevel" => $this->victimToAttackerConnection->accessLevel]);
        }

        if (isset($this->attackerToVictimConnection)) {
            if (empty($this->attackerId)) {
                throw new \InvalidArgumentException("connection implies authorized");
            }
            $this->attackerConnectionId = $I->createConnection($this->attackerId, $this->victimId, $this->attackerToVictimConnection->approved, null, ["AccessLevel" => $this->attackerToVictimConnection->accessLevel]);
        }
    }

    public function wantToTest(\TestSymfonyGuy $I)
    {
        $I->wantToTest(json_encode(get_object_vars($this)));
    }
}
