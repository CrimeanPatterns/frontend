<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Updater\EventsChannelMigrator;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use Codeception\Example;

/**
 * @group mobile
 * @group functional-symfony
 */
class Update2ControllerCest extends BaseTraitCest
{
    use JsonHeaders;
    private const HOST = '127.0.0.1';

    /**
     * @dataProvider migrateEventsChannelDataProvider
     */
    public function migrateEventsChannel(\TestSymfonyGuy $I, Example $case)
    {
        [
            'sessionKey' => $sessionKey,
            'userProvidedChannel' => $userProvidedChannel,
            'expectedResult' => $expectedResult,
        ] = $case;
        /** @var EventsChannelMigrator $migrator */
        $migrator = $I->grabService(EventsChannelMigrator::class);
        $token = $migrator->send($sessionKey);

        $this->sendMigrateRequest($I, $token);
        $I->seeResponseContainsJson(['success' => true]);
        $this->sendAuthRequest($I, $originalData = [
            'channels' => [$userProvidedChannel],
            'client' => StringUtils::getRandomCode(20),
        ]);

        if ($expectedResult) {
            $I->seeResponseContainsJson([$userProvidedChannel => [
                'sign' => 'fake',
                'info' => '',
            ]]);
        } else {
            $I->seeResponseContainsJson([$userProvidedChannel => [
                'status' => 403,
            ]]);
        }
        // subsequent request is always invalid
        $this->sendAuthRequest($I, $originalData);
        $I->seeResponseContainsJson([$userProvidedChannel => [
            'status' => 403,
        ]]);
    }

    public function migrateEventsChannelLockout(\TestSymfonyGuy $I): void
    {
        $migrator = $I->grabService(EventsChannelMigrator::class);
        $validToken = $migrator->send(StringUtils::getRandomCode(20));

        try {
            foreach (\range(1, 100) as $_) {
                $this->sendMigrateRequest($I, StringUtils::getRandomCode(40));
                $I->seeResponseContainsJson(['success' => false]);
            }

            $this->sendMigrateRequest($I, $validToken);
            $I->seeResponseContainsJson(['success' => false]);
        } finally {
            $I->resetLockout('centrifuge_channel_auth', self::HOST);
        }
    }

    protected function sendMigrateRequest(\TestSymfonyGuy $I, string $token): void
    {
        $I->sendPost(
            '/m/api/account/update2/migrate-events-channel',
            ['token' => $token]
        );
    }

    protected function sendAuthRequest(\TestSymfonyGuy $I, array $data): void
    {
        $I->sendPost(
            '/centrifuge/auth/',
            $data
        );
    }

    protected function migrateEventsChannelDataProvider()
    {
        return [
            [
                'sessionKey' => $sessionKey = StringUtils::getRandomCode(20),
                'userProvidedChannel' => "\$update_session_$sessionKey",
                'expectedResult' => true,
            ],
            [
                'sessionKey' => $sessionKey = StringUtils::getRandomCode(20),
                'userProvidedChannel' => "\$update_session_invalid",
                'expectedResult' => false,
            ],
        ];
    }
}
