<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\AccountController\AccountFormJsonFormatCest;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Clock\ClockNative;
use Clock\ClockTest;
use Codeception\Example;
use Duration\Duration;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @psalm-type TestExample = array{case: FormTestCase}
 * @group frontend-functional
 * @group mobile
 */
class DocumentFormJsonFormatCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;
    use JsonHeaders;

    private const BASE_JSON_PATH = 'account/mobile/form/document/';
    private const OLD_MOBILE_VERSION = '4.41.23+b100500';
    private const NEW_MOBILE_VERSION = '4.42.0+b100500';
    private ?\DateTimeImmutable $baseDate;

    public function __construct()
    {
        $this->baseDate = new \DateTimeImmutable('2023-06-01 12:00:00');
    }

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->baseDate = new \DateTimeImmutable('2023-06-01 12:00:00');
        $I->mockService(
            ClockNative::class,
            new ClockTest(Duration::fromDateTime($this->baseDate))
        );
    }

    /**
     * @dataProvider formatExistingDocumentDataProvider
     * @param Example|TestExample $example
     */
    public function testFormatExistingDocument(
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
        $I->sendGet("/m/api/document/{$docId}");
        $I->seeResponseCodeIsSuccessful();
        $I->expectJsonTemplate($case->getJsonPath(), $I->grabResponse());
    }

    /**
     * @dataProvider formatNewDocumentDataProvider
     * @param Example|TestExample $example
     */
    public function testFormatNewAccount(
        \TestSymfonyGuy $I,
        Example $example
    ) {
        /** @var FormTestCase $case */
        $case = $example['case'];
        $I->wantToTest($case->getName());
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, $case->getMobileVersion());
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $I->sendGet("/m/api/provider/{$case->getProvider()}");
        $I->seeResponseCodeIsSuccessful();
        $I->expectJsonTemplate($case->getJsonPath(), $I->grabResponse());
    }

    /**
     * @return array<string, TestExample>
     */
    private function formatExistingDocumentDataProvider(): array
    {
        return it([
            FormTestCase::new('existing passport doc on old version')
                ->setProvider(Providercoupon::TYPE_PASSPORT)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/old_passport.json'))
                ->setFields($passportFields = [
                    'ProgramName' => 'Passport',
                    'Kind' => PROVIDER_KIND_DOCUMENT,
                    'CustomFields' => \json_encode([
                        'passport' => [
                            'name' => 'Passport name',
                            'number' => 'Pass number',
                            'issueDate' => \json_decode(\json_encode($this->baseDate), true),
                            'country' => '16',
                        ],
                    ]),
                    'ExpirationDate' => $this->baseDate->modify('+10 years')->format('Y-m-d H:i:s'),
                    'Description' => 'Some comment',
                ]),
            FormTestCase::new('existing passport doc on redesign 2023 Fall version')
                ->setProvider(Providercoupon::TYPE_PASSPORT)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/redesign_2023_fall_passport.json'))
                ->setFields($passportFields),

            FormTestCase::new('existing trusted-traveler doc on old version')
                ->setProvider(Providercoupon::TYPE_TRUSTED_TRAVELER)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/old_trusted_traveler.json'))
                ->setFields($trustedTravelerFields = [
                    'ProgramName' => 'Trusted Traveler',
                    'Kind' => PROVIDER_KIND_DOCUMENT,
                    'CustomFields' => \json_encode([
                        'trustedTraveler' => [
                            'travelerNumber' => '1000500',
                        ],
                    ]),
                    'ExpirationDate' => $this->baseDate->modify('+1 year')->format('Y-m-d H:i:s'),
                    'Description' => 'Some comment',
                ]),
            FormTestCase::new('existing passport doc on redesign 2023 Fall version')
                ->setProvider(Providercoupon::TYPE_TRUSTED_TRAVELER)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/redesign_2023_fall_trusted_traveler.json'))
                ->setFields($trustedTravelerFields),
        ])
        ->map(fn (FormTestCase $case) => $case->toCase())
        ->toArray();
    }

    private function formatNewDocumentDataProvider(): array
    {
        return it([
            FormTestCase::new('new passport on old version')
                ->setProvider(Providercoupon::KEY_TYPE_PASSPORT)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('new/old_passport.json')),
            FormTestCase::new('new passport on redesign 2023 Fall version')
                ->setProvider(Providercoupon::KEY_TYPE_PASSPORT)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('new/redesign_2023_fall_passport.json')),

            FormTestCase::new('new trusted traveler on old version')
                ->setProvider(Providercoupon::KEY_TYPE_TRAVELER_NUMBER)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('new/old_trusted_traveler.json')),
            FormTestCase::new('new trusted traveler on redesign 2023 Fall version')
                ->setProvider(Providercoupon::KEY_TYPE_TRAVELER_NUMBER)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('new/redesign_2023_fall_trusted_traveler.json')),
        ])
        ->map(fn (FormTestCase $case) => $case->toCase())
        ->toArray();
    }

    private static function fromRelativePath(string $path)
    {
        return codecept_data_dir(self::BASE_JSON_PATH . $path);
    }
}
