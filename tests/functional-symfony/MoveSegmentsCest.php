<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;

/**
 * @group frontend-functional
 */
class MoveSegmentsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $login;
    private $userId;

    private $uaId;
    private $segmentId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = 'test' . $I->grabRandomString();
        $this->userId = $I->createAwUser($this->login, null, [], false, true);
        $I->setCookie("NDEnabled", "1");
        $I->sendGET("/page/about?_switch_user={$this->login}");
        $I->saveCsrfToken();

        $I->createFamilyMember($this->userId, 'Test', 'Member');

        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->userId,
            "ConfNo" => 'Rest1',
            "Address" => "London",
            "Name" => "Birthday",
            "StartDate" => date("Y-m-d H:i:s", strtotime("tomorrow")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 5),
        ]);
        $I->grabService(CacheManager::class)->invalidateTags([Tags::getTimelineKey($this->userId)]);
        $I->sendGET("/timeline/data");
        $timeline = $I->grabDataFromResponseByJsonPath("")[0];
        $I->assertEquals(2, count($timeline['segments']));

        $this->uaId = $timeline['agents'][1]['id'];
        $this->segmentId = $timeline['segments'][1]['id'];
    }

    public function moveSegment(\TestSymfonyGuy $I)
    {
        $I->sendPOST("/timeline/move/{$this->segmentId}/{$this->uaId}");
        $I->sendGET("/timeline/data/{$this->uaId}");
        $timeline = $I->grabDataFromResponseByJsonPath("")[0];
        $I->assertEquals(0, $timeline['agents'][0]['count']);
        $I->assertEquals(1, $timeline['agents'][1]['count']);
    }

    public function copySegment(\TestSymfonyGuy $I)
    {
        $I->sendPOST("/timeline/move/{$this->segmentId}/{$this->uaId}", ['copy' => 'true']);
        $I->sendGET("/timeline/data/{$this->uaId}");
        $timeline = $I->grabDataFromResponseByJsonPath("")[0];
        $I->assertEquals(1, $timeline['agents'][0]['count']);
        $I->assertEquals(1, $timeline['agents'][1]['count']);
    }
}
