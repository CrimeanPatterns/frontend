<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Codeception\Module\Aw;
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 * @group moscow
 */
class TipCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Router */
    private $router;

    private $routeName = 'aw_profile_settings';

    private $tip = [
        'Title' => 'tip-test-title',
        'Description' => 'tip-test-description',
        'ReshowInterval' => 7,
        'Route' => 'aw_profile_settings',
        'Element' => 'headerTimelineButtonLink',
        'SortIndex' => 0,
    ];

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->createUserAndLogin($I, 'account-', 'userpass-', [], true);
        $I->createAwAccount($this->userId, Aw::TEST_PROVIDER_ID, 'test.login');

        $this->router = $I->grabService('router');
        $this->createTrip($I);
        $this->tip['TipID'] = (int) $I->shouldHaveInDatabase('Tip', $this->tip);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);
        $I->grabService('database_connection')->executeQuery('DELETE FROM Tip WHERE TipID = ' . $this->tip['TipID']);
    }

    public function tipMarkReadAndCheckAnywayShow(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);

        $I->sendAjaxPostRequest($this->router->generate('aw_tip_mark', ['tipId' => $this->tip['TipID']]), ['event' => 'click']);
        $I->seeInDatabase('UserTip', [
            'UserID' => $this->userId,
            'TipID' => $this->tip['TipID'],
            'CloseDate' => null,
        ]);
        $I->amOnPage($this->router->generate($this->routeName));
        $I->dontSeeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => $this->tip['TipID']]);

        $I->amOnPage($this->router->generate($this->routeName, ['tip' => 'headerTimelineButtonLink']));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);
    }

    public function tipDisabledTest(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);

        $I->executeQuery('
            UPDATE Tip
            SET Enabled = 0
            WHERE TipID  = ' . $this->tip['TipID'] . '
        ');

        $I->amOnPage($this->router->generate($this->routeName));
        $I->dontSeeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro]', ['data-tipid' => $this->tip['TipID']]);
    }

    public function tipOutput(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);
    }

    public function tipOutputWithRefreshPageWithoutMarkRead(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);

        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);
    }

    public function tipMarkReadCheck(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);

        $I->sendAjaxPostRequest($this->router->generate('aw_tip_mark', ['tipId' => $this->tip['TipID']]), ['event' => 'close']);
        $I->seeInDatabase('UserTip', [
            'UserID' => $this->userId,
            'TipID' => $this->tip['TipID'],
            'ClickDate' => null,
        ]);
        $I->amOnPage($this->router->generate($this->routeName));
        $I->dontSeeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => $this->tip['TipID']]);
    }

    public function tipMarkReadWithCloseEvent(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);

        $I->sendAjaxPostRequest($this->router->generate('aw_tip_mark', ['tipId' => $this->tip['TipID']]), ['event' => 'close']);
        $closeDate = $I->grabFromDatabase('UserTip', 'CloseDate', [
            'UserID' => $this->userId,
            'TipID' => $this->tip['TipID'],
            'ClickDate' => null,
        ]);
        $I->assertNotNull($closeDate);
    }

    public function tipReshowIntervalCheckOutput(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);

        $I->sendAjaxPostRequest($this->router->generate('aw_tip_mark', ['tipId' => $this->tip['TipID']]), ['event' => 'close']);
        $I->amOnPage($this->router->generate($this->routeName));
        $I->dontSeeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => $this->tip['TipID']]);

        $I->executeQuery('
            UPDATE UserTip
            SET
                ShowDate = "' . date('Y-m-d H:i:s', time() - (86400 * ($this->tip['ReshowInterval'] + 1))) . '"
            WHERE
                    TipID  = ' . $this->tip['TipID'] . '
                AND UserID = ' . $this->userId . '
            LIMIT 1');
        $I->amOnPage($this->router->generate($this->routeName));
        $I->seeElement('a[href="' . $this->router->generate('aw_timeline') . '"][data-intro][data-show]', ['data-tipid' => (string) $this->tip['TipID']]);
    }

    public function tipJavascriptTest(\TestSymfonyGuy $I)
    {
        $tipGroupTimeline = $I->grabFromDatabase('Tip', 'TipID', [
            'Route' => 'aw_timeline',
            'Element' => 'timelineShareButton',
        ]);

        if (empty($tipGroupTimeline)) {
            unset($this->tip['TipID']);
            $this->tip['Route'] = 'aw_timeline';
            $this->tip['Element'] = 'timelineShareButton';
            $this->tip['TipID'] = $I->shouldHaveInDatabase('Tip', $this->tip);
        }
        $I->amOnPage($this->router->generate('aw_timeline'));

        $I->seeInSource('selector: \'a[data-ng-click="segment.shareTravelplanDialog = true"]:visible\'');
    }

    private function createTrip(\TestSymfonyGuy $I)
    {
        $I->haveInDatabase('Rental', [
            'UserID' => $this->userId,
            'PickupLocation' => 'Moscow, Russia',
            'DropoffLocation' => 'Perm, Russia',
            'Number' => 'TEST_NUMBER',
            'PickupPhone' => 'TEST_PICK_UP_PHONE',
            'DropoffPhone' => 'TEST_DROP_OFF_PHONE',
            'PickupDatetime' => date('Y-m-d H:i:s', time() + 86400),
            'DropoffDatetime' => date('Y-m-d H:i:s', time() + 86400 + 3600),

            'RentalCompanyName' => 'TEST_PROVIDER',
            'ProviderID' => null,
            'Notes' => 'TEST_NOTES',
            'Cancelled' => false,
            'ChangeDate' => null,
            'Type' => Rental::TYPE_TAXI,
        ]);
        $I->sendAjaxGetRequest($this->router->generate('aw_timeline_data'));
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
    }
}
