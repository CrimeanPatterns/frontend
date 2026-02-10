<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\Tests\Unit\BaseUserTest;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group frontend-unit
 */
class TimelineSharingTest extends BaseUserTest
{
    /**
     * @var int
     */
    private $agentId;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * @var Useragent
     */
    private $userAgent;

    /**
     * @var Useragent
     */
    private $familyMember;

    public function _before()
    {
        parent::_before();
        $this->agentId = $this->aw->createAwUser(null, null, [], true);
        $this->reservationId = $this->db->haveInDatabase('Reservation', [
            'HotelName' => "Some hotel",
            'CheckInDate' => (new \DateTime('+1 year'))->format("Y-m-d H:i:s"),
            'CheckOutDate' => (new \DateTime('+1 year 8 day'))->format("Y-m-d H:i:s"),
            'UserID' => $this->agentId,
            'CreateDate' => (new \DateTime())->format("Y-m-d H:i:s"),
        ]);
        $this->authChecker = $this->container->get("security.authorization_checker");
        $agentRepo = $this->container->get('doctrine')->getRepository(Useragent::class);
        $this->userAgent = $agentRepo->find($this->aw->createConnection($this->agentId, $this->user->getUserid(), false));
        $this->familyMember = $agentRepo->find($this->aw->createFamilyMember($this->agentId, 'Lucie', 'Liu'));
    }

    public function _after()
    {
        $this->authChecker = null;
        $this->userAgent = null;
        parent::_after();
    }

    public function testNotApproved()
    {
        $this->assertFalse($this->authChecker->isGranted('EDIT_TIMELINE', $this->userAgent));
    }

    public function testApproved()
    {
        $this->userAgent->setIsapproved(true);
        $this->assertFalse($this->authChecker->isGranted('EDIT_TIMELINE', $this->userAgent));
    }

    public function testReadOnlyShare()
    {
        $this->userAgent->setIsapproved(true);
        $this->aw->shareAwTimeline($this->agentId, null, $this->user->getUserid());
        $this->assertFalse($this->authChecker->isGranted('EDIT_TIMELINE', $this->userAgent));
    }

    public function testWriteShare()
    {
        $this->userAgent->setIsapproved(true);
        $this->userAgent->setTripAccessLevel(TRIP_ACCESS_FULL_CONTROL);
        $this->aw->shareAwTimeline($this->agentId, null, $this->user->getUserid());
        $this->assertTrue($this->authChecker->isGranted('EDIT_TIMELINE', $this->userAgent));
    }

    public function testFMMainShare()
    {
        $this->userAgent->setIsapproved(true);
        $this->userAgent->setTripAccessLevel(TRIP_ACCESS_FULL_CONTROL);
        $this->aw->shareAwTimeline($this->agentId, null, $this->user->getUserid());
        $this->assertFalse($this->authChecker->isGranted('EDIT_TIMELINE', $this->familyMember));
    }

    public function testFMWriteShare()
    {
        $this->userAgent->setIsapproved(true);
        $this->userAgent->setTripAccessLevel(TRIP_ACCESS_FULL_CONTROL);
        $this->aw->shareAwTimeline($this->agentId, $this->familyMember->getUseragentid(), $this->user->getUserid());
        $this->assertTrue($this->authChecker->isGranted('EDIT_TIMELINE', $this->familyMember));
    }

    public function testFMReadOnlyShare()
    {
        $this->userAgent->setIsapproved(true);
        $this->aw->shareAwTimeline($this->agentId, $this->familyMember->getUseragentid(), $this->user->getUserid());
        $this->assertFalse($this->authChecker->isGranted('EDIT_TIMELINE', $this->familyMember));
    }

    public function testFMNotApproved()
    {
        $this->userAgent->setTripAccessLevel(TRIP_ACCESS_FULL_CONTROL);
        $this->aw->shareAwTimeline($this->agentId, $this->familyMember->getUseragentid(), $this->user->getUserid());
        $this->assertFalse($this->authChecker->isGranted('EDIT_TIMELINE', $this->familyMember));
    }
}
