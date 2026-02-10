<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\AccountController\AccountFormJsonFormatCest;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Example;
use PHPUnit\Framework\ExpectationFailedException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @psalm-type TestExample = array{case: FormTestCase}
 * @group frontend-functional
 * @group mobile
 */
class AccountFormJsonFormatCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;
    use JsonHeaders;

    private const BASE_JSON_PATH = 'account/mobile/form/account/';
    private const PROVIDER_CUSTOM = 'custom';
    private const OLD_MOBILE_VERSION = '4.41.23+b100500';
    private const NEW_MOBILE_VERSION = '4.42.0+b100500';

    /**
     * @dataProvider formatExistingAccountDataProvider
     * @param Example|TestExample $example
     */
    public function testFormatExistingAccount(
        \TestSymfonyGuy $I,
        Example $example
    ) {
        /** @var FormTestCase $case */
        $case = $example['case'];
        $I->wantToTest($case->getName());

        if ($case->getCallableBefore()) {
            $beforeData = ($case->getCallableBefore())($I, $case);
        }

        $provider = $beforeData['provider'] ?? $case->getProvider();

        $accountId = $I->createAwAccount(
            $this->user->getId(),
            self::PROVIDER_CUSTOM === $provider ? null : $provider,
            $case->getLogin() ?? null,
            $case->getPassword() ?? '',
            $case->getFields() ?? []
        );

        if ($case->getCallableAfter()) {
            ($case->getCallableAfter())($I, $case, $accountId, $beforeData ?? null);
        }

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, $case->getMobileVersion());
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $I->sendGet("/m/api/account/{$accountId}");
        $I->seeResponseCodeIsSuccessful();
        $I->expectJsonTemplate($case->getJsonPath(), $I->grabResponse());
    }

    /**
     * @dataProvider formatNewAccountDataProvider
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

    public function unitedNewAccountOnOldVersion(\TestSymfonyGuy $I): void
    {
        try {
            $I->seeInDatabase('Param', ['Name' => 'unitedAnswerJson']);
            $hasUnitedQuestions = true;
        } catch (ExpectationFailedException $e) {
            $hasUnitedQuestions = false;
        }

        $example = new Example(
            self::makeCase(
                'new united on old version',
                Provider::UNITED_ID,
                self::OLD_MOBILE_VERSION,
                $hasUnitedQuestions ?
                    'new/old_united_with_questions.json' :
                    'new/old_united.json'
            )
            ->toCase()
        );
        $this->testFormatNewAccount($I, $example);
    }

    public function unitedNewAccountOnRedesign2023FallVersion(\TestSymfonyGuy $I): void
    {
        try {
            $I->seeInDatabase('Param', ['Name' => 'unitedAnswerJson']);
            $hasUnitedQuestions = true;
        } catch (ExpectationFailedException $e) {
            $hasUnitedQuestions = false;
        }

        $example = new Example(
            self::makeCase(
                'new united on redesign 2023 Fall version',
                Provider::UNITED_ID,
                self::NEW_MOBILE_VERSION,
                $hasUnitedQuestions ?
                    'new/redesign_2023_fall_united_with_questions.json' :
                    'new/redesign_2023_fall_united.json'
            )
            ->toCase()
        );
        $this->testFormatNewAccount($I, $example);
    }

    /**
     * @return array<string, TestExample>
     */
    private function formatExistingAccountDataProvider(): array
    {
        return it([
            FormTestCase::new('existing safeway account on old version')
                ->setProvider(Provider::SAFEWAY_ID)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/old_safeway.json'))
                ->setFields($safewayAccFields = [
                    'Login2' => 'safeway',
                    'PwnedTimes' => 100500,
                    'CheckedBy' => 1,
                ]),
            FormTestCase::new('existing safeway account on new version')
                ->setProvider(Provider::SAFEWAY_ID)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/redesign_2023_fall_safeway.json'))
                ->setFields($safewayAccFields),

            FormTestCase::new('existing custom account on old version, half filled')
                ->setProvider(self::PROVIDER_CUSTOM)
                ->setMobileVersion(self::OLD_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/old_custom.json'))
                ->setFields($customAccountFields = [
                    'ProgramName' => 'Hilton (Honors)',
                    'Login' => 'custom login',
                    'Login2' => 'custom login2',
                    'Comment' => 'custom comment',
                    'Balance' => 100,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                ]),
            FormTestCase::new('existing custom account on new version, half filled')
                ->setProvider(self::PROVIDER_CUSTOM)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/redesign_2023_fall_custom.json'))
                ->setFields($customAccountFields),
            FormTestCase::new('existing chase account on new version')
                ->setProvider(Provider::CHASE_ID)
                ->setMobileVersion(self::NEW_MOBILE_VERSION)
                ->setJsonPath(self::fromRelativePath('existing/redesign_2023_fall_chase.json'))
                ->setFields([
                    'Login' => 'someuserid',
                    'PwnedTimes' => 100500,
                    'CheckedBy' => 1,
                ]),
        ])
        ->map(fn (FormTestCase $case) => $case->toCase())
        ->toArray();
    }

    private function formatNewAccountDataProvider(): array
    {
        return it([
            self::makeCase(
                'new custom account on old version',
                self::PROVIDER_CUSTOM,
                self::OLD_MOBILE_VERSION,
                'new/old_custom.json',
            ),
            self::makeCase(
                'new testprovider on old version',
                Provider::TEST_PROVIDER_ID,
                self::OLD_MOBILE_VERSION,
                'new/old_testprovider.json',
            ),
            self::makeCase(
                'new capitalone on old version',
                Provider::CAPITAL_ONE_ID,
                self::OLD_MOBILE_VERSION,
                'new/old_capitalone_oauth.json',
            ),
            self::makeCase(
                'new delta on old version',
                Provider::DELTA_ID,
                self::OLD_MOBILE_VERSION,
                'new/old_delta.json',
            ),
            self::makeCase(
                'new southwest on old version',
                Provider::SOUTHWEST_ID,
                self::OLD_MOBILE_VERSION,
                'new/old_southwest.json'
            ),
            self::makeCase(
                'new custom account on redesign 2023Fall version',
                self::PROVIDER_CUSTOM,
                self::NEW_MOBILE_VERSION,
                'new/redesign_2023_fall_custom.json',
            ),
            self::makeCase(
                'new testprovider on redesign 2023Fall version',
                Provider::TEST_PROVIDER_ID,
                self::NEW_MOBILE_VERSION,
                'new/redesign_2023_fall_testprovider.json',
            ),
            self::makeCase(
                'new capitalone on redesign 2023Fall version',
                Provider::CAPITAL_ONE_ID,
                self::NEW_MOBILE_VERSION,
                'new/redesign_2023_fall_capitalone_oauth.json',
            ),
            self::makeCase(
                'new delta on redesign 2023Fall version',
                Provider::DELTA_ID,
                self::NEW_MOBILE_VERSION,
                'new/redesign_2023_fall_delta.json',
            ),
            self::makeCase(
                'new southwest on redesign 2023Fall version',
                Provider::SOUTHWEST_ID,
                self::NEW_MOBILE_VERSION,
                'new/redesign_2023_fall_southwest.json'
            ),
            self::makeCase(
                'new chase on redesign 2023Fall version',
                Provider::CHASE_ID,
                self::NEW_MOBILE_VERSION,
                'new/redesign_2023_fall_chase.json'
            ),
        ])
        ->map(fn (FormTestCase $case) => $case->toCase())
        ->toArray();
    }

    private static function fromRelativePath(string $path)
    {
        return codecept_data_dir(self::BASE_JSON_PATH . $path);
    }

    private static function makeCase(string $caseName, string $provider, string $mobileVersion, string $jsonPath): FormTestCase
    {
        return FormTestCase::new($caseName)
            ->setProvider($provider)
            ->setMobileVersion($mobileVersion)
            ->setJsonPath(self::fromRelativePath($jsonPath));
    }
}
