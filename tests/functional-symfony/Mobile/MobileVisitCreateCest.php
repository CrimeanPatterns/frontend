<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Entity\PageVisit;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class MobileVisitCreateCest
{
    use AutoVerifyMocksTrait;

    private ?int $userId;
    private string $username;
    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);
        $this->router = $I->grabService('router');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userId = null;
        $this->username = '';
        $this->router = null;
    }

    public function checkVisitCreate(\TestSymfonyGuy $I)
    {
        $I->sendGet($this->router->generate('awm_new_login_status', ['_switch_user' => $this->username]));
        $I->saveCsrfToken();

        $I->haveHttpHeader('Content-Type', 'application/json');
        $route = $this->router->generate('awm_api_visits_create');
        $I->sendPost($route, ['screenName' => 'AccountsList']);
        $I->sendPost($route, ['screenName' => 'TravelSummary']);
        $I->sendPost($route, ['screenName' => 'AccountsList']);
        $I->seeResponseIsJson();

        $visits = $I->query("
            SELECT `PageName`, `UserID`, `Visits`, `Day`, `IsMobile`
            FROM `PageVisit`
            WHERE `UserID` = :userId
            ORDER BY `PageName` ASC;",
            [':userId' => $this->userId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $today = (new \DateTime())->format('Y-m-d');
        $I->assertEquals([
            ['PageName' => PageVisitLogger::PAGE_ACCOUNT_LIST, 'UserID' => $this->userId, 'Visits' => 2, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_MOBILE],
            ['PageName' => PageVisitLogger::PAGE_TRAVEL_SUMMARY_REPORT, 'UserID' => $this->userId, 'Visits' => 1, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_MOBILE],
        ], $visits);
    }

    public function checkVisitCreateFromBlog(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');

        $refCode = $I->grabFromDatabase('Usr', 'RefCode', ['UserID' => $this->userId]);
        $I->sendPost($this->router->generate('awm_api_visits_create_blog'), ['screenName' => 'Blog', 'refCode' => $refCode]);
        $I->seeResponseIsJson();

        $visits = $I->query("SELECT * FROM `PageVisit` WHERE `UserID` = :userId;", [':userId' => $this->userId])
            ->fetchAll(\PDO::FETCH_ASSOC);

        $today = (new \DateTime())->format('Y-m-d');
        $I->assertEquals([
            ['PageName' => PageVisitLogger::PAGE_BLOG, 'UserID' => $this->userId, 'Visits' => 1, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_MOBILE],
        ], $visits);
    }
}
