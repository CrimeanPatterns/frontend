<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Event\AccountChangedEvent;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use AwardWallet\MainBundle\Service\BackgroundCheckUpdater;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class BackgroundCheckUpdaterTest extends BaseUserTest
{
    private ?BackgroundCheckUpdater $updater;

    public function _before()
    {
        parent::_before();

        $this->updater = new BackgroundCheckUpdater(
            $this->container->get('doctrine.dbal.default_connection'),
            $this->container->get('doctrine.dbal.default_connection'),
            $this->container->get(LoggerInterface::class)
        );
    }

    public function _after()
    {
        $this->updater = null;

        parent::_after();
    }

    /**
     * @dataProvider updateProviderDataProvider
     */
    public function testUpdateProvider(
        array $providerFields,
        array $accountFields,
        int $expectedBackgroundCheck,
        int $accountLevel = ACCOUNT_LEVEL_FREE,
        bool $hasMailbox = false
    ) {
        [$providerId, $accountId] = $this->prepare($providerFields, $accountFields, $accountLevel, $hasMailbox);
        $this->assertEquals($expectedBackgroundCheck === 1 ? 0 : 1, $this->updater->updateProvider($providerId));
        $this->assertEquals(
            $expectedBackgroundCheck,
            $this->db->grabFromDatabase('Account', 'BackgroundCheck', ['AccountID' => $accountId])
        );
    }

    public function updateProviderDataProvider(): array
    {
        return [
            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['Disabled' => 0, 'ErrorCode' => ACCOUNT_CHECKED],
                1,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['Disabled' => 0, 'ErrorCode' => ACCOUNT_CHECKED],
                0,
                ACCOUNT_LEVEL_FREE,
                false,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['Disabled' => 1, 'ErrorCode' => ACCOUNT_CHECKED],
                0,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['ErrorCode' => ACCOUNT_INVALID_PASSWORD],
                1,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['ErrorCode' => ACCOUNT_QUESTION],
                1,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 1],
                ['ErrorCode' => ACCOUNT_QUESTION],
                0,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 1],
                ['ErrorCode' => ACCOUNT_CHECKED, 'Pass' => 'test'],
                1,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 1],
                ['UserAgentID' => 1, 'ErrorCode' => ACCOUNT_CHECKED, 'Pass' => 'test'],
                1,
                ACCOUNT_LEVEL_FREE,
                true,
            ],

            [
                ['State' => PROVIDER_CHECKING_OFF], [], 0,
            ],

            [
                ['State' => PROVIDER_CHECKING_EXTENSION_ONLY], [], 0,
            ],

            [
                ['State' => PROVIDER_FIXING], [], 0,
            ],

            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0],
                ['Disabled' => 0, 'ErrorCode' => ACCOUNT_CHECKED],
                0,
                ACCOUNT_LEVEL_FREE,
            ],

            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0],
                ['Disabled' => 0, 'ErrorCode' => ACCOUNT_UNCHECKED],
                0,
                ACCOUNT_LEVEL_FREE,
            ],

            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0],
                ['Disabled' => 0, 'ErrorCode' => ACCOUNT_CHECKED],
                1,
                ACCOUNT_LEVEL_AWPLUS,
            ],

            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0],
                ['Disabled' => 0, 'ErrorCode' => ACCOUNT_UNCHECKED],
                1,
                ACCOUNT_LEVEL_AWPLUS,
            ],
        ];
    }

    /**
     * @dataProvider onPlusChangedDataProvider
     */
    public function testOnPlusChanged(
        array $providerFields,
        array $accountFields,
        int $expectedBackgroundCheck,
        int $accountLevel = ACCOUNT_LEVEL_FREE
    ) {
        [$providerId, $accountId] = $this->prepare($providerFields, $accountFields, $accountLevel, false);
        $this->updater->onPlusChanged(new UserPlusChangedEvent($this->user->getId()));
        $this->assertEquals(
            $expectedBackgroundCheck,
            $this->db->grabFromDatabase('Account', 'BackgroundCheck', ['AccountID' => $accountId])
        );
    }

    public function onPlusChangedDataProvider(): array
    {
        return [
            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0], [], 0, ACCOUNT_LEVEL_FREE,
            ],

            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0], [], 1, ACCOUNT_LEVEL_AWPLUS,
            ],
        ];
    }

    /**
     * @dataProvider onAccountChangedDataProvider
     */
    public function testOnAccountChanged(
        array $providerFields,
        array $accountFields,
        int $expectedBackgroundCheck,
        int $accountLevel = ACCOUNT_LEVEL_FREE,
        bool $hasMailbox = false
    ) {
        [$providerId, $accountId] = $this->prepare($providerFields, $accountFields, $accountLevel, $hasMailbox);
        $this->updater->onAccountChanged(new AccountChangedEvent($accountId));
        $this->assertEquals(
            $expectedBackgroundCheck,
            $this->db->grabFromDatabase('Account', 'BackgroundCheck', ['AccountID' => $accountId])
        );
    }

    public function onAccountChangedDataProvider(): array
    {
        return [
            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0], [], 0, ACCOUNT_LEVEL_FREE,
            ],

            [
                ['State' => PROVIDER_ENABLED, 'PasswordRequired' => 0], [], 1, ACCOUNT_LEVEL_AWPLUS,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['ErrorCode' => ACCOUNT_QUESTION],
                0,
                ACCOUNT_LEVEL_FREE,
                false,
            ],

            [
                ['State' => PROVIDER_CHECKING_WITH_MAILBOX, 'PasswordRequired' => 0],
                ['ErrorCode' => ACCOUNT_CHECKED],
                1,
                ACCOUNT_LEVEL_FREE,
                true,
            ],
        ];
    }

    private function prepare(array $providerFields, array $accountFields, int $accountLevel, bool $hasMailbox)
    {
        $this->user->setAccountlevel($accountLevel);
        $this->user->setValidMailboxesCount($hasMailbox ? 1 : 0);
        $this->em->flush();

        $providerId = $this->aw->createAwProvider(null, null, $providerFields);
        $accountId = $this->aw->createAwAccount($this->user->getId(), $providerId, 'bc_test', null, $accountFields);

        return [$providerId, $accountId];
    }
}
