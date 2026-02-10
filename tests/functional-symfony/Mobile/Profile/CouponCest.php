<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Profile;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;

/**
 * @group frontend-functional
 * @group mobile
 */
class CouponCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use JsonHeaders;
    use JsonForm;

    protected $route;
    protected $resultRoute;
    protected $code;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $I->resetLockout('forgot', '127.0.0.1');
        $I->sendGET('/m/api/login_status?_switch_user=' . $this->user->getLogin());
        $this->route = $I->grabService('router')->generate('aw_mobile_usecoupon');
        $this->resultRoute = $I->grabService('router')->generate('aw_mobile_usecoupon_result');
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.20.0+100500');
        $I->saveCsrfToken();
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        if (!empty($this->code)) {
            /** @var \Doctrine\DBAL\Connection $conn */
            $conn = $I->grabService('doctrine')
                ->getConnection();
            $conn->exec("
              DELETE
                c
              FROM
                Cart c
                LEFT JOIN Coupon n
                  ON n.CouponID = c.CouponID
              WHERE n.Code = " . $conn->quote($this->code) . "
            ");
            $conn->exec("
              DELETE FROM Coupon WHERE Code = " . $conn->quote($this->code) . "
            ");
        }
    }

    public function successAwPlus(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('UsefulCoupon-');
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'UsefulCoupon',
            'Code' => $this->code,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'Firsttimeonly' => 0,
        ]);

        $I->sendPUT($this->route, [
            'coupon' => $this->code,
        ]);

        $I->seeResponseContainsJson(['success' => true, 'next' => ['route' => 'index.profile-edit']]);
        $I->sendGET($this->resultRoute);
        $I->seeResponseJsonMatchesJsonPath('$.children..rows');
    }

    public function expiredAwPlus(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('ExpiredCoupon-');
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'ExpiredCoupon',
            'Code' => $this->code,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('-1 year')),
            'MaxUses' => 1,
        ]);

        $I->sendPUT($this->route, [
            'coupon' => $this->code,
        ]);

        $I->seeResponseJsonMatchesJsonPath('$.children[1][errors][0]');
        $I->assertEquals('Expired coupon code', $I->grabDataFromJsonResponse('children.1.errors.0'));
    }

    public function inviteBonus(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('Invite-' . $this->user->getUserid() . '-');
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'Invite bonus',
            'Code' => $this->code,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'Firsttimeonly' => 0,
        ]);

        $I->sendPUT($this->route, [
            'coupon' => $this->code,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'next' => ['route' => 'index.profile-edit']]);
        $I->sendGET($this->resultRoute);

        $I->seeResponseJsonMatchesJsonPath('$.children[0].name');
        $I->assertStringContainsString('Free upgrade confirmation', $I->grabDataFromJsonResponse('children.0.name'));
    }

    public function inviteBonusViaGet(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('Invite-' . $this->user->getUserid() . '-');
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'Invite bonus',
            'Code' => $this->code,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'Firsttimeonly' => 0,
        ]);

        $route = $this->route . '?code=' . $this->code;
        $I->sendGET($route);
        $I->seeResponseContainsJson(['success' => true, 'next' => ['route' => 'index.profile-edit']]);
        $I->sendGET($this->resultRoute);

        $I->seeResponseJsonMatchesJsonPath('$.children[0].name');
        $I->assertStringContainsString('Free upgrade confirmation', $I->grabDataFromJsonResponse('children.0.name'));
    }

    public function inviteBonusViaGetExpired(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('Invite-' . $this->user->getUserid() . '-');
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'Invite bonus',
            'Code' => $this->code,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('-1 year')),
            'MaxUses' => 1,
        ]);

        $route = $this->route . '?code=' . $this->code;
        $I->sendGET($route);

        $I->seeResponseJsonMatchesJsonPath('$.children[1][errors][0]');
        $I->assertEquals('Expired coupon code', $I->grabDataFromJsonResponse('children.1.errors.0'));
    }

    public function onecard(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('Invite-' . $this->user->getUserid() . '-');
        $couponId = $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'Invite bonus',
            'Code' => $this->code,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'Firsttimeonly' => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => OneCard::TYPE]);

        $I->sendPUT($this->route, [
            'coupon' => $this->code,
        ]);
        $I->seeResponseContainsJson(['success' => true, 'next' => ['route' => 'index.profile-edit']]);
        $I->sendGET($this->resultRoute);

        $I->assertStringContainsString('order AwardWallet OneCards', $I->grabDataFromJsonResponse('children.0.message'));
    }

    public function discount25(\TestSymfonyGuy $I)
    {
        $this->code = uniqid('Invite-' . $this->user->getUserid() . '-');
        $couponId = $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'Invite bonus',
            'Code' => $this->code,
            'Discount' => 25,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);

        $I->sendPUT($this->route, [
            'coupon' => $this->code,
        ]);
        $I->assertEquals('This coupon could be used only via desktop version of the site', $I->grabDataFromJsonResponse('children.1.errors.0'));
    }

    public function invalidCsrf(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('X-XSRF-TOKEN', '-1');
        $route = $this->route . '?code=code';
        $I->sendGET($route);
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['CSRF failed']);
    }
}
