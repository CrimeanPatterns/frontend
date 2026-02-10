<?php

namespace AwardWallet\Tests\Modules\Access;

class TimelineAccessScenario extends Scenario
{
    protected const TIMELINE_MY = 'my';
    protected const TIMELINE_FAMILY_MEMBER = 'family-member';
    /**
     * @var int
     */
    public $familyMemberId;
    protected $timelineType;
    protected $writeAccess;

    public function __construct($expectedAction, $authorized, ?Connection $victimToAttackerConnection = null, ?Connection $attackerToVictimConnection = null, $shared = false, $writeAccess = false, string $timelineType = self::TIMELINE_MY)
    {
        parent::__construct($expectedAction, $authorized, $victimToAttackerConnection, $attackerToVictimConnection, $shared);

        $this->timelineType = $timelineType;
        $this->writeAccess = $writeAccess;
        $this->victimStaff = true;
    }

    public function create(\TestSymfonyGuy $I)
    {
        parent::create($I);

        if ($this->timelineType === self::TIMELINE_FAMILY_MEMBER) {
            $this->familyMemberId = $I->createFamilyMember($this->victimId, 'Kitty', 'Cat');
        } elseif ($this->timelineType === self::TIMELINE_MY) {
            // nothing
        } else {
            throw new \Exception("Invalid timeline type: $this->timelineType");
        }

        if ($this->shared) {
            if (empty($this->victimConnectionId)) {
                throw new \InvalidArgumentException("shared implies victimToAttacker connection");
            }
            $I->haveInDatabase('TimelineShare', ['TimelineOwnerID' => $this->victimId, 'RecipientUserID' => $this->attackerId, 'FamilyMemberID' => $this->familyMemberId, 'UserAgentID' => $this->victimConnectionId]);
        }

        if ($this->writeAccess) {
            if (empty($this->victimConnectionId)) {
                throw new \InvalidArgumentException("writeAccess implies victimToAttacker connection");
            }
            $I->executeQuery("update UserAgent set TripAccessLevel = 1 where UserAgentID = {$this->victimConnectionId}");
        }
    }
}
