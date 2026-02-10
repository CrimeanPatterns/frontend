<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Entity\PageVisit;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class BlogVisitCreateCest
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
        $I->amOnPage($this->router->generate('aw_account_list', ['_switch_user' => $this->username]));
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');

        $route = $this->router->generate('aw_blog_visits_create');
        $I->sendPost($route, ['pageName' => PageVisitLogger::PAGE_BLOG]);
        $I->sendPost($route, ['pageName' => PageVisitLogger::PAGE_COMMUNITY]);
        $I->sendPost($route, ['pageName' => PageVisitLogger::PAGE_CREDIT_CARD_OFFERS]);
        $I->sendPost($route, ['pageName' => PageVisitLogger::PAGE_BLOG]);

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);

        $visits = $I->query("
            SELECT `PageName`, `UserID`, `Visits`, `Day`, `IsMobile`
            FROM `PageVisit`
            WHERE `UserID` = :userId
            ORDER BY `PageName` ASC;",
            [':userId' => $this->userId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $today = (new \DateTime())->format('Y-m-d');
        $I->assertEquals([
            ['PageName' => PageVisitLogger::PAGE_ACCOUNT_LIST, 'UserID' => $this->userId, 'Visits' => 1, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_NOT_MOBILE],
            ['PageName' => PageVisitLogger::PAGE_BLOG, 'UserID' => $this->userId, 'Visits' => 2, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_NOT_MOBILE],
            ['PageName' => PageVisitLogger::PAGE_COMMUNITY, 'UserID' => $this->userId, 'Visits' => 1, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_NOT_MOBILE],
            ['PageName' => PageVisitLogger::PAGE_CREDIT_CARD_OFFERS, 'UserID' => $this->userId, 'Visits' => 1, 'Day' => $today, 'IsMobile' => PageVisit::TYPE_NOT_MOBILE],
        ], $visits);
    }

    public function checkVisitCreateUnauthorized(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');

        $I->sendPost($this->router->generate('aw_blog_visits_create'), ['pageName' => PageVisitLogger::PAGE_TRANSFER_TIMES]);

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => false, 'message' => 'Invalid request data']);
    }

    public function checkVisitCreateEmptyParam(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_list', ['_switch_user' => $this->username]));
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');

        $I->sendPost($this->router->generate('aw_blog_visits_create'), ['pageName' => '']);

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => false, 'message' => '"PageName" cannot be blank.']);
    }
}
