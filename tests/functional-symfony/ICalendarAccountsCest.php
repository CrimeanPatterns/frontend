<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ExpirationDate\ExpirationDate;
use Codeception\Module\Aw;

/**
 * @group frontend-functional
 */
class ICalendarAccountsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $code;
    private $userId;
    private $codeAgent;
    private $userAgentId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->code = StringUtils::getRandomCode(32);
        //		$this->userId = $I->createAwUser('John', null, ['AccExpireCalendarCode' => $this->code]);
        $this->codeAgent = StringUtils::getRandomCode(32);

        $this->userId = $I->createAwUser(null, null, [
            'FirstName' => 'John',
            'LastName' => 'Malkovich',
            'AccExpireCalendarCode' => $this->code,
        ]);
        $this->userAgentId = $I->haveInDatabase('UserAgent', [
            'AgentID' => $this->userId,
            'FirstName' => 'Ivan',
            'LastName' => 'Ivanov',
            'AccExpireCalendarCode' => $this->codeAgent,
            'IsApproved' => 1,
        ]);
    }

    public function testSuccessLink(\TestSymfonyGuy $I)
    {
        $I->createAwAccount($this->userId, Aw::TEST_PROVIDER_ID, 'testLogin', null, ['Balance' => 100]);

        $I->sendGET("/iCal/accExpire/$this->code");
        $I->see("VCALENDAR");
        $I->dontSee("EVENT");
    }

    public function testCoupon(\TestSymfonyGuy $I)
    {
        $expiration = "+6 days";
        $noDateExp = [];

        foreach (ExpirationDate::PASSPORT_NOTICES_MONTHS as $diff) {
            $noDateExp[] = date('Ymd', strtotime("- {$diff} months", strtotime($expiration)));
        }
        $dateExp[] = $date = date('Ymd', strtotime($expiration));

        foreach (ExpirationDate::BALANCE_NOTIFICATION_DAYS_LAST_WEEK as $diff) {
            $dateExp[] = date('Ymd', strtotime("- {$diff} days", strtotime($expiration)));
        }
        $I->createAwCoupon(
            $this->userId,
            'Passport',
            1,
            'My passport',
            $this->createInfo($expiration, 1, false)
        );

        $I->sendGET("/iCal/accExpire/$this->code");
        $I->see("VCALENDAR");
        $I->see("EVENT");
        //        foreach ($dateExp as $date) {
        $I->see("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");

        //        }
        foreach ($noDateExp as $date) {
            $I->dontSee("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");
        }
    }

    public function testCouponUa(\TestSymfonyGuy $I)
    {
        $expiration = "+6 days";
        $noDateExp = [];

        foreach (ExpirationDate::PASSPORT_NOTICES_MONTHS as $diff) {
            $noDateExp[] = date('Ymd', strtotime("- {$diff} months", strtotime($expiration)));
        }
        $dateExp[] = $date = date('Ymd', strtotime($expiration));

        foreach (ExpirationDate::BALANCE_NOTIFICATION_DAYS_LAST_WEEK as $diff) {
            $dateExp[] = date('Ymd', strtotime("- {$diff} days", strtotime($expiration)));
        }
        $I->createAwCoupon(
            $this->userId,
            'Passport',
            1,
            'My passport',
            array_merge($this->createInfo($expiration, 1, false), ['UserAgentID' => $this->userAgentId])
        );

        $I->sendGET("/iCal/accExpire/$this->codeAgent");
        $I->see("VCALENDAR");
        $I->see("EVENT");
        //        foreach ($dateExp as $date) {
        $I->see("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");

        //        }
        foreach ($noDateExp as $date) {
            $I->dontSee("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");
        }
    }

    public function testPassport(\TestSymfonyGuy $I)
    {
        $expiration = "+6 months";
        $dateExp[] = $date = date('Ymd', strtotime($expiration));

        foreach (ExpirationDate::PASSPORT_NOTICES_MONTHS as $diff) {
            $dateExp[] = date('Ymd', strtotime("- {$diff} months", strtotime($expiration)));
        }
        $I->createAwCoupon(
            $this->userId,
            'Passport',
            1,
            'My passport',
            $this->createInfo($expiration, 1, true)
        );

        $I->sendGET("/iCal/accExpire/$this->code");
        $I->see("VCALENDAR");
        $I->see("EVENT");
        //        foreach ($dateExp as $date) {
        $I->see("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");
        //        }
    }

    public function testPassportUa(\TestSymfonyGuy $I)
    {
        $expiration = "+6 months";
        $dateExp[] = $date = date('Ymd', strtotime($expiration));

        foreach (ExpirationDate::PASSPORT_NOTICES_MONTHS as $diff) {
            $dateExp[] = date('Ymd', strtotime("- {$diff} months", strtotime($expiration)));
        }
        $I->createAwCoupon(
            $this->userId,
            'Passport',
            1,
            'My passport',
            array_merge($this->createInfo($expiration, 1, true), ['UserAgentID' => $this->userAgentId])
        );

        $I->sendGET("/iCal/accExpire/$this->codeAgent");
        $I->see("VCALENDAR");
        $I->see("EVENT");
        //        foreach ($dateExp as $date) {
        $I->see("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");
        //        }
    }

    public function testAccount(\TestSymfonyGuy $I)
    {
        $expiration = "+7 days";
        $date = date('Ymd', strtotime($expiration));

        $I->createAwAccount(
            $this->userId,
            'aeroplan',
            'expiration.close',
            '',
            $this->createInfo($expiration)
        );

        $I->sendGET("/iCal/accExpire/$this->code");
        $I->see("VCALENDAR");
        $I->see("EVENT");
        $I->see("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");
    }

    public function testAccountUa(\TestSymfonyGuy $I)
    {
        $expiration = "+7 days";
        $date = date('Ymd', strtotime($expiration));
        $I->createAwAccount(
            $this->userId,
            'aeroplan',
            'expiration.close',
            '',
            array_merge($this->createInfo($expiration), ['UserAgentID' => $this->userAgentId])
        );

        $I->sendGET("/iCal/accExpire/$this->codeAgent");
        $I->see("VCALENDAR");
        $I->see("EVENT");
        $I->see("BEGIN:VEVENT\nDTSTART;VALUE=DATE:{$date}\n");
    }

    private function createInfo(string $expiration = '+7 days', int $balance = 1000, ?bool $isPassport = null)
    {
        $result = [
            'ExpirationDate' => date('Y-m-d', strtotime($expiration)),
        ];

        if (isset($isPassport)) {
            $result['TypeID'] = $isPassport ? Providercoupon::TYPE_PASSPORT : 0;
        } else {
            $result['Balance'] = $balance;
            $result['State'] = 1;
            $result['UpdateDate'] = $result['SuccessCheckDate'] = date('Y-m-d', strtotime('-1 minute'));
        }

        return $result;
    }
}
