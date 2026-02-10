<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Socialad;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 *
 * https://redmine.awardwallet.com/issues/8764
 * https://redmine.awardwallet.com/issues/11071
 * https://redmine.awardwallet.com/issues/14823
 */
class AdManagerTest extends BaseUserTest
{
    public const US_IP = '10.10.10.10';
    public const NO_US_IP = '10.10.10.11';

    /**
     * @var AdManager
     */
    protected $adManager;

    /**
     * @var UsrRepository
     */
    protected $userRep;

    public function _before()
    {
        parent::_before();
        $mock = $this->mockServiceWithBuilder(GeoLocation::class);
        $mock->method('getCountryIdByIp')->willReturnCallback(function ($ip) {
            if ($ip == self::NO_US_IP) {
                return 1;
            }

            if ($ip == self::US_IP) {
                return 230;
            }

            return null;
        });
        $this->db->executeQuery("delete from SocialAd where InternalNote = '" . addslashes(self::class) . "'");
        $this->adManager = $this->container->get('aw.manager.advt');
        $this->userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
    }

    public function _after()
    {
        $this->adManager = $this->userRep = null;
        parent::_after();
    }

    public function testEmptyAdvt()
    {
        $this->assertNull($this->adManager->getAdvt($this->getOptions()));
    }

    public function testKind()
    {
        $this->addAdvt([
            'Kind' => ADKIND_BALANCE_CHECK,
        ]);
        $this->assertNull($this->adManager->getAdvt($this->getOptions(ADKIND_EMAIL)));
        $this->assertNotNull($this->adManager->getAdvt($this->getOptions(ADKIND_BALANCE_CHECK)));
    }

    /**
     * @dataProvider periodProvider
     */
    public function testPeriod(?\DateTime $begin = null, ?\DateTime $end = null, $exists = true)
    {
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_BALANCE_CHECK,
            'BeginDate' => is_null($begin) ? $begin : $begin->format("Y-m-d H:i:s"),
            'EndDate' => is_null($end) ? $end : $end->format("Y-m-d H:i:s"),
        ]);
        $advt = $this->adManager->getAdvt($this->getOptions(ADKIND_BALANCE_CHECK));

        if ($exists) {
            $this->assertNotNull($advt);
            $this->assertEquals($advtId, $advt->getSocialadid());
        } else {
            $this->assertNull($advt);
        }
    }

    public function periodProvider()
    {
        return [
            [null, null, true],
            [new \DateTime("-1 day"), null, true],
            [new \DateTime("+1 day"), null, false],
            [null, new \DateTime("-1 day"), false],
            [null, new \DateTime("+1 day"), true],
            [new \DateTime("-1 day"), new \DateTime("+1 day"), true],
            [new \DateTime("+1 day"), new \DateTime("+3 day"), false],
        ];
    }

    public function testMailKind()
    {
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
        ]);
        $this->assertNotNull($this->adManager->getAdvt($this->getOptions(ADKIND_EMAIL)));
        $this->assertNotNull($this->adManager->getAdvt($this->getOptions(ADKIND_EMAIL, 'abc')));

        $this->db->haveInDatabase("AdTypeMail", [
            'SocialAdID' => $advtId,
            'TypeMail' => 'abc',
        ]);
        $this->assertNotNull($advt = $this->adManager->getAdvt($this->getOptions(ADKIND_EMAIL, 'abc')));
        $this->assertEquals($advtId, $advt->getSocialadid());
        $this->assertNull($this->adManager->getAdvt($this->getOptions(ADKIND_EMAIL, 'cba')), $advtId);
    }

    public function testBookerAdvtMembers()
    {
        /** @var Usr $booker */
        /** @var Usr $business */
        [$booker, $business] = $this->addBooker();
        $this->aw->createConnection($this->user->getUserid(), $business->getUserid(), true);
        $this->aw->createConnection($business->getUserid(), $this->user->getUserid(), true);
        $this->user->setOwnedByBusiness($business);
        $business->getBookerInfo()->setDisableAd(false);
        $this->em->flush();

        // add advt
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
        ]);
        $opt = $this->getOptions(ADKIND_EMAIL);
        $opt->user = $this->user;

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());

        $business->getBookerInfo()->setDisableAd(true);
        $this->em->flush();

        $this->assertNull($this->adManager->getAdvt($opt));

        $this->db->haveInDatabase("AdBooker", [
            'SocialAdID' => $advtId,
            'BookerID' => $business->getUserid(),
        ]);

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());

        $business->getBookerInfo()->setDisableAd(false);
        $this->em->flush();

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());
    }

    public function testBookerAdvtOtherUsers()
    {
        /** @var Usr $booker */
        /** @var Usr $business */
        [$booker, $business] = $this->addBooker();
        $business->getBookerInfo()->setDisableAd(false);
        $this->em->flush();

        // add advt
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
        ]);
        $this->db->haveInDatabase("AdBooker", [
            'SocialAdID' => $advtId,
            'BookerID' => $business->getUserid(),
        ]);

        $opt = $this->getOptions(ADKIND_EMAIL);
        $opt->user = $this->user;

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());

        $business->getBookerInfo()->setDisableAd(true);
        $this->em->flush();

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());
    }

    public function testProviderFilter()
    {
        $accRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $a1 = $accRep->find($this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "test", "", [
            'ChangeCount' => 5,
        ]));
        $a2 = $accRep->find($this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "test", "", [
            'ChangeCount' => 2,
        ]));
        $kind = $this->db->grabFromDatabase("Provider", "Kind", ["ProviderID" => Aw::TEST_PROVIDER_ID]);
        $opt = $this->getOptions(ADKIND_EMAIL);

        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
            'AllProviders' => 0,
            'ProviderKind' => $kind,
        ]);

        $this->assertNull($this->adManager->getAdvt($opt));

        $opt->accounts = [$a1, $a2];

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());

        $this->updateAdvt($advtId, [
            'ProviderKind' => PROVIDER_KIND_HOTEL,
        ]);

        $this->assertNull($this->adManager->getAdvt($opt));

        $this->updateAdvt($advtId, [
            'ProviderKind' => null,
        ]);
        $this->assertNull($this->adManager->getAdvt($opt));
        $this->db->haveInDatabase("AdProvider", [
            'SocialAdID' => $advtId,
            'ProviderID' => Aw::TEST_PROVIDER_ID,
        ]);

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());

        $opt->accounts = [];
        $this->assertNull($this->adManager->getAdvt($opt));
        $opt->providers = [$a1->getProviderid()];

        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $advt->getSocialadid());
    }

    public function testGeoLocationGroups()
    {
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
            'GeoGroups' => Socialad::GEO_GROUP_US,
        ]);
        $this->db->haveInDatabase("AdTypeMail", [
            'SocialAdID' => $advtId,
            'TypeMail' => 'abc',
        ]);

        $opt = $this->getOptions(ADKIND_EMAIL);
        $this->assertNull($this->adManager->getAdvt($opt));
        $opt->emailType = 'abc';
        $this->assertNull($this->adManager->getAdvt($opt));
        $opt->clientIp = self::NO_US_IP;
        $this->assertNull($this->adManager->getAdvt($opt));
        $opt->clientIp = self::US_IP;
        $this->assertNotNull($ad = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $ad->getSocialadid());
        $opt->clientIp = self::NO_US_IP;
        $this->assertNull($this->adManager->getAdvt($opt));
        $this->updateAdvt($advtId, [
            'GeoGroups' => null,
        ]);
        $this->assertNotNull($advt = $this->adManager->getAdvt($opt));
        $this->em->refresh($advt);
        $this->assertFalse($advt->hasGeoGroup(Socialad::GEO_GROUP_US));
        $advt->addGeoGroup(Socialad::GEO_GROUP_US);
        $this->assertTrue($advt->hasGeoGroup(Socialad::GEO_GROUP_US));
        $advt->removeGeoGroup(Socialad::GEO_GROUP_US);
        $this->assertFalse($advt->hasGeoGroup(Socialad::GEO_GROUP_US));
        $advt->setGeoGroups(Socialad::GEO_GROUP_ALL);
        $this->assertFalse($advt->hasGeoGroup(Socialad::GEO_GROUP_US));
        $advt->setGeoGroups(Socialad::GEO_GROUP_US);
        $this->assertTrue($advt->hasGeoGroup(Socialad::GEO_GROUP_US));
    }

    public function testNonUsGeoGroup()
    {
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
            'GeoGroups' => Socialad::GEO_GROUP_NON_US,
        ]);
        $opt = $this->getOptions(ADKIND_EMAIL);
        $opt->clientIp = self::US_IP;
        $this->assertNull($this->adManager->getAdvt($opt));
        $opt->clientIp = self::NO_US_IP;
        $this->assertNotNull($ad = $this->adManager->getAdvt($opt));
        $this->assertEquals($advtId, $ad->getSocialadid());
        $this->updateAdvt($advtId, [
            'GeoGroups' => Socialad::GEO_GROUP_US,
        ]);
        $this->assertNull($this->adManager->getAdvt($opt));
        $opt->clientIp = self::US_IP;
        $this->assertNotNull($this->adManager->getAdvt($opt));
    }

    public function testAdSent()
    {
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
            'GeoGroups' => Socialad::GEO_GROUP_NON_US,
        ]);
        $opt = $this->getOptions(ADKIND_EMAIL);
        $this->assertNotNull($this->adManager->getAdvt($opt));
        $this->db->seeInDatabase("AdStat", ["SocialAdID" => $advtId, "Sent" => 1]);
        $this->assertNotNull($this->adManager->getAdvt($opt));
        $this->db->seeInDatabase("AdStat", ["SocialAdID" => $advtId, "Sent" => 2]);
        $opt->clientIp = self::US_IP;
        $this->assertNull($this->adManager->getAdvt($opt));
        $this->db->seeInDatabase("AdStat", ["SocialAdID" => $advtId, "Sent" => 2]);
    }

    public function testRecordStat()
    {
        $advtId = $this->addAdvt([
            'Kind' => ADKIND_EMAIL,
        ]);
        $this->db->dontSeeInDatabase('AdStat', ['SocialAdID' => $advtId]);
        $this->adManager->recordStat($advtId);
        $this->db->seeInDatabase('AdStat', ['SocialAdID' => $advtId, 'Messages' => 1]);
        $this->adManager->recordStat($advtId);
        $this->db->seeInDatabase('AdStat', ['SocialAdID' => $advtId, 'Messages' => 2]);
    }

    private function addBooker()
    {
        $bookerId = $this->aw->createAwBookerStaff('testbook' . StringHandler::getPseudoRandomString(5), 'abc');
        $businessId = $this->db->grabFromDatabase('UserAgent', 'ClientID', ['AgentID' => $bookerId]);

        return [
            $this->userRep->find($bookerId),
            $this->userRep->find($businessId),
        ];
    }

    private function getOptions($advtKind = ADKIND_SOCIAL, $emailType = null)
    {
        $opt = new Options($advtKind, $this->user, $emailType);
        $opt->filter = "InternalNote = '" . addslashes(self::class) . "'";

        return $opt;
    }

    private function addAdvt(array $fields)
    {
        return $this->db->haveInDatabase('SocialAd', array_merge([
            'Name' => StringHandler::getPseudoRandomString(10),
            'AllProviders' => 1,
            'InternalNote' => self::class,
        ], $fields));
    }

    private function updateAdvt($id, array $fields)
    {
        $up = [];

        foreach ($fields as $field => $value) {
            $value = is_null($value) ? 'null' : "'" . addslashes($value) . "'";
            $up[] = "$field = $value";
        }
        $this->db->executeQuery("update SocialAd set " . implode(", ", $up) . " where SocialAdID = " . intval($id));
    }
}
