<?php

namespace AwardWallet\Tests\FunctionalSymfony\Business;

use AwardWallet\MainBundle\Entity\Usr;

/**
 * @group frontend-functional
 */
class MembersDropdownCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $business;

    /**
     * @var Usr
     */
    private $businessAdmin;

    /**
     * @var Usr
     */
    private $user;

    /**
     * @var int
     */
    private $familyMemberId;

    public function _before(\TestSymfonyGuy $I)
    {
        $users = $I->getContainer()->get('doctrine')->getRepository(Usr::class);
        $this->user = $users->find($I->createAwUser(null, null, ['FirstName' => 'Jameson', 'LastName' => 'Fox']));
        $this->familyMemberId = $I->createFamilyMember($this->user->getUserid(), "Samantha", "Fox");
        $this->business = $users->find($I->createBusinessUserWithBookerInfo(null, ['Company' => 'Elite']));
        $this->businessAdmin = $users->find($I->createStaffUserForBusinessUser($this->business->getUserid()));
        $I->amOnBusiness();
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->user = null;
        $this->business = null;
        $this->businessAdmin = null;
    }

    public function testTimelineBusiness(\TestSymfonyGuy $I)
    {
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Elite', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->see("label\":\"Elite");
    }

    public function testTimelineNoConnection(\TestSymfonyGuy $I)
    {
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->dontSee("Jameson");
    }

    public function testTimelineWithConnection(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->dontSee("Jameson");
    }

    public function testTimelineWithShare(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->see("label\":\"Jameson Fox");
    }

    public function testTimelineReadonlyAdd(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', 'add' => 'true', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->dontSee("Jameson");
    }

    public function testTimelineFullControlAdd(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid(), null, null, ['TripAccessLevel' => 1]);
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', 'add' => 'true', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->see("label\":\"Jameson Fox");
    }

    public function testTimelineFamilyMemberByConnected(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid(), null, null, ['TripAccessLevel' => 1]);
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->shareAwTimeline($this->user->getUserid(), $this->familyMemberId, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', 'add' => 'true', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->see("label\":\"Samantha Fox");
        $I->see("label\":\"Jameson Fox");
    }

    public function testWrongText(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Walker', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->dontSee("Jameson");
    }

    public function testTimelineWithShareNotApproved(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid(), false);
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->dontSee("Jameson");
    }

    public function testTimelineFamilyMemberNoAccess(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->shareAwTimeline($this->user->getUserid(), null, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Samantha', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->dontSee("Samantha");
    }

    public function testTimelineFamilyMemberApproved(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->shareAwTimeline($this->user->getUserid(), $this->familyMemberId, $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_timeline"), ['q' => 'Samantha', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->see("label\":\"Samantha Fox");
    }

    public function testAccountsNoConnection(\TestSymfonyGuy $I)
    {
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseEquals('[]');
    }

    public function testAccountsNotApproved(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid(), false);
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseEquals('[]');
    }

    public function testAccountsApproved(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Fox', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->see("label\":\"Samantha Fox");
        $I->see("label\":\"Jameson Fox");
    }

    public function testAccountsWrong(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Bad', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseEquals('[]');
    }

    public function testAccountsReadonly(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid(), null, null, ['AccessLevel' => ACCESS_READ_ALL]);
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Fox', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->see("label\":\"Samantha Fox");
        $I->see("label\":\"Jameson Fox");
    }

    public function testAccountsReadonlyAdd(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid(), null, null, ['AccessLevel' => ACCESS_READ_ALL]);
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Fox', 'add' => 'true', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseEquals('[]');
    }

    public function testAccountsFullAdd(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Fox', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->see("label\":\"Samantha Fox");
        $I->see("label\":\"Jameson Fox");
    }

    public function testAccountsFamilyMemberByMain(\TestSymfonyGuy $I)
    {
        $I->createConnection($this->user->getUserid(), $this->business->getUserid());
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Jameson', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->see("label\":\"Samantha Fox");
        $I->see("label\":\"Jameson Fox");
    }

    public function testAccountsBusiness(\TestSymfonyGuy $I)
    {
        $I->sendGET($I->getContainer()->get("router")->generate("aw_business_members_dropdown_accounts"), ['q' => 'Elite', '_switch_user' => $this->businessAdmin->getLogin()]);
        $I->seeResponseIsJson();
        $I->see("label\":\"Elite");
    }
}
