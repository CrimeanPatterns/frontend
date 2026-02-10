<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providervote;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Service\Account\ManualUpdateHelper;
use AwardWallet\MainBundle\Service\Account\ManualUpdateResult;
use AwardWallet\Tests\Modules\DbBuilder\AccountProperty;
use AwardWallet\Tests\Modules\DbBuilder\EliteLevel;
use AwardWallet\Tests\Modules\DbBuilder\ProviderProperty;
use AwardWallet\Tests\Modules\DbBuilder\TextEliteLevel;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class ManualUpdateHelperTest extends BaseContainerTest
{
    private ?Usr $user;
    private ?Provider $provider;
    private ?ManualUpdateHelper $helper;

    public function _before()
    {
        parent::_before();
        $userId = $this->aw->createAwUser('us_' . StringUtils::getPseudoRandomString(8));
        $providerId = $this->aw->createAwProvider($name = 'pr_' . StringUtils::getPseudoRandomString(8), $name, [
            'State' => PROVIDER_ENABLED,
            'Kind' => PROVIDER_KIND_AIRLINE,
            'DisplayName' => 'Delta Air Lines ' . StringUtils::getRandomCode(8),
            'ProgramName' => 'SkyMiles',
            'Currency' => Currency::MILES_ID,
        ]);

        $this->user = $this->em->getRepository(Usr::class)->find($userId);
        $this->provider = $this->em->getRepository(Provider::class)->find($providerId);

        $this->mockService(UserMailboxCounter::class, $this->makeEmpty(UserMailboxCounter::class, [
            'total' => function ($userId, $validOnly) {
                return 1;
            },
        ]));
        $this->helper = new ManualUpdateHelper(
            $this->em,
            $this->container->get(ElitelevelRepository::class),
            $this->container->get(UserMailboxCounter::class)
        );
    }

    public function _after()
    {
        $this->user = null;
        $this->provider = null;
        $this->helper = null;
        parent::_after();
    }

    public function testGetAccountInfoWithLevel()
    {
        \Cache::getInstance()->delete('TextEliteLevel_v2');

        $accountId = $this->aw->createAwAccount($this->user->getId(), $this->provider->getId(), StringUtils::getRandomCode(8), 'password', [
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 5000,
        ]);

        $this->createLevelProperty($accountId);
        $this->createEliteLevels();

        $account = $this->em->getRepository(Account::class)->find($accountId);
        $result = $this->helper->getData($account);

        $this->assertInstanceOf(ManualUpdateResult::class, $result);
        $this->assertEquals('Member', $result->getEliteLevel());
        $this->assertEquals(['Member', 'Silver', 'Gold', 'Platinum', 'Diamond'], $result->getEliteLevelOptions());
        $this->assertEquals(true, $result->isMailboxConnected());
        $this->assertEquals(false, $result->isNotifyMe());
    }

    public function testGetAccountInfoWithoutLevel()
    {
        $accountId = $this->aw->createAwAccount($this->user->getId(), $this->provider->getId(), StringUtils::getRandomCode(8), 'password', [
            'ErrorCode' => ACCOUNT_PROVIDER_ERROR,
            'Balance' => 1000,
        ]);

        $providerVote = (new Providervote())
            ->setProviderid($this->provider)
            ->setUserid($this->user)
            ->setVotedate(new \DateTime());

        $this->em->persist($providerVote);
        $this->em->flush();

        $account = $this->em->getRepository(Account::class)->find($accountId);
        $result = $this->helper->getData($account);

        $this->assertInstanceOf(ManualUpdateResult::class, $result);
        $this->assertEquals(null, $result->getEliteLevel());
        $this->assertEquals(null, $result->getEliteLevelOptions());
        $this->assertEquals(true, $result->isMailboxConnected());
        $this->assertEquals(true, $result->isNotifyMe());
    }

    private function createLevelProperty($accountId): void
    {
        $providerProperty = new ProviderProperty('Level', [
            'ProviderID' => $this->provider->getId(),
            'Name' => 'Membership Level',
            'Kind' => PROPERTY_KIND_STATUS,
            'SortIndex' => 20,
        ]);
        $this->dbBuilder->makeAccountProperty(
            new AccountProperty($providerProperty, 'SkyMiles Member', ['AccountID' => $accountId])
        );
    }

    private function createEliteLevels(): void
    {
        $levels = [
            ['Rank' => 0, 'Name' => 'Member', 'Keywords' => ['SkyMiles Member', 'Member']],
            ['Rank' => 1, 'Name' => 'Silver', 'Keywords' => ['Silver Medallion']],
            ['Rank' => 2, 'Name' => 'Gold', 'Keywords' => ['Gold Medallion', 'Gold Medallion | Sky Priority']],
            ['Rank' => 3, 'Name' => 'Platinum', 'Keywords' => ['Platinum Medallion']],
            ['Rank' => 4, 'Name' => 'Diamond', 'Keywords' => ['Diamond Medallion']],
        ];

        foreach ($levels as $level) {
            $texts = [];

            foreach ($level['Keywords'] as $keyword) {
                $texts[] = new TextEliteLevel($keyword);
            }

            $this->dbBuilder->makeEliteLevel(
                new EliteLevel($level['Rank'], $level['Name'], null, null, $texts, [
                    'ProviderID' => $this->provider->getId(),
                    'ByDefault' => 1,
                ])
            );
        }
    }
}
