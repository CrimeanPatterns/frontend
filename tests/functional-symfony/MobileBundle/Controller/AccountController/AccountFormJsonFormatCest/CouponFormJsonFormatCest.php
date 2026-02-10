<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\AccountController\AccountFormJsonFormatCest;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @psalm-type TestExample = array{case: FormTestCase}
 * @group frontend-functional
 * @group mobile
 */
class CouponFormJsonFormatCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;
    use JsonHeaders;

    private const BASE_JSON_PATH = 'account/mobile/form/coupon/';
    private const OLD_MOBILE_VERSION = '4.41.23+b100500';
    private const NEW_MOBILE_VERSION = '4.42.0+b100500';

    /**
     * @dataProvider formatExistingDataProvider
     * @param Example|TestExample $example
     */
    public function testFormatExisting(
        \TestSymfonyGuy $I,
        Example $example
    ) {
        /** @var FormTestCase $case */
        $case = $example['case'];
        $I->wantToTest($case->getName());

        if ($case->getCallableBefore()) {
            $beforeData = ($case->getCallableBefore())($I, $case);
        }

        $docId = $I->createAwCoupon(
            $this->user->getId(),
            $case->getLogin() ?? null,
            null,
            null,
            \array_merge(
                ['TypeID' => $case->getProvider()],
                $case->getFields()
            )
        );

        if ($case->getCallableAfter()) {
            ($case->getCallableAfter())($I, $case, $docId, $beforeData ?? null);
        }

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, $case->getMobileVersion());
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $I->sendGet("/m/api/coupon/{$docId}");
        $I->seeResponseCodeIsSuccessful();
        $I->expectJsonTemplate($case->getJsonPath(), $I->grabResponse());
    }

    /**
     * @return array<string, TestExample>
     */
    public function formatExistingDataProvider(): array
    {
        return it([
            FormTestCase::new('existing coupon on old version')
                ->setProvider(Providercoupon::TYPE_TICKET_COMPANION)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/old.json'))
                ->setFields($couponFields = [
                    'Description' => 'note here',
                    'Value' => 100500,
                    'ExpirationDate' => '2024-11-14 00:00:00',
                    'ProgramName' => 'Company name',
                    'CardNumber' => 'Cert number',
                    'Pin' => 'Pin code',
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'CurrencyID' => 45,
                ]),
            FormTestCase::new('existing coupon on redesign 2023 fall version')
                ->setProvider(Providercoupon::TYPE_TICKET_COMPANION)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/redesign_2023_fall.json'))
                ->setFields($couponFields),
        ])
        ->map(fn (FormTestCase $case) => $case->toCase())
        ->toArray();
    }

    /**
     * @dataProvider formatNewDataProvider
     * @param Example|TestExample $example
     */
    public function testFormatNew(
        \TestSymfonyGuy $I,
        Example $example
    ) {
        /** @var FormTestCase $case */
        $case = $example['case'];
        $I->wantToTest($case->getName());
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, $case->getMobileVersion());
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $I->sendGet("/m/api/provider/coupon");
        $I->seeResponseCodeIsSuccessful();
        $I->expectJsonTemplate($case->getJsonPath(), $I->grabResponse());
    }

    private function formatNewDataProvider(): array
    {
        return it([
            FormTestCase::new('new passport on old version')
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('new/old.json')),
            FormTestCase::new('new passport on redesign 2023 fall version')
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('new/redesign_2023_fall.json')),
        ])
        ->map(fn (FormTestCase $case) => $case->toCase())
        ->toArray();
    }

    private static function fromRelativePath(string $path)
    {
        return codecept_data_dir(self::BASE_JSON_PATH . $path);
    }
}
