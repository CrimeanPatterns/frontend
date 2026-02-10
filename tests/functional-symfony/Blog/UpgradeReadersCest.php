<?php

namespace AwardWallet\Tests\FunctionalSymfony\Blog;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus6Months;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Blog\UpgradeReaders;

/**
 * @group frontend-functional
 */
class UpgradeReadersCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?UpgradeReaders $upgradeReaders;
    private ?UsrRepository $userRepository;
    private ?Usr $realUpgradeUser;
    private ?Usr $fakeUpgradeUser;
    private ?Usr $fakeNonUpgradeUser2;
    private ?Usr $nonUpgradeUser;

    private array $packUsersNeedUpgrade = [];

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->upgradeReaders = $I->grabService(UpgradeReaders::class);

        $fields = ['Accounts' => UpgradeReaders::CONDITION_MIN_ACCOUNTS + 1];
        $this->realUpgradeUser = $this->userRepository->find(
            $I->createAwUser(null, null, $fields + ['FirstName' => 'needUpgrade-real'])
        );

        // for UpgradeReaders $limitFakeUpgrade (10% from real)
        for ($i = 0; $i < UpgradeReaders::CONDITION_PERCENT_FROM_REAL; $i++) {
            $usr = $this->userRepository->find($I->createAwUser(null, null, $fields + ['FirstName' => 'needUpgrade-' . $i]));
            $this->conditionUserData($I, $usr, UpgradeReaders::CONDITION_MIN_EARNING_SUM + 1);
            $this->packUsersNeedUpgrade[] = $usr->getId();
        }

        $this->nonUpgradeUser = $this->userRepository->find(
            $I->createAwUser(null, null, $fields + ['FirstName' => 'dontUpgrade-' . date('H:i:s')])
        );
        $this->fakeNonUpgradeUser2 = $this->userRepository->find(
            $I->createAwUser(null, null, $fields + ['FirstName' => 'dontUpgrade-fake-' . date('H:i:s')])
        );
        $this->fakeUpgradeUser = $this->userRepository->find(
            $I->createAwUser(null, null, $fields + ['FirstName' => 'needFakeUpgrade-' . date('H:i:s')])
        );
    }

    public function upgrade(\TestSymfonyGuy $I)
    {
        $this->conditionUserData($I, $this->realUpgradeUser, UpgradeReaders::CONDITION_MIN_EARNING_SUM + 1);
        $this->conditionUserData($I, $this->fakeUpgradeUser, UpgradeReaders::CONDITION_MAX_EARNING_SUM);
        $this->conditionUserData($I, $this->fakeNonUpgradeUser2, UpgradeReaders::CONDITION_MAX_EARNING_SUM);
        $this->conditionUserData($I, $this->nonUpgradeUser, UpgradeReaders::CONDITION_MIN_EARNING_SUM);

        $this->upgradeReaders->execute();

        foreach ($this->packUsersNeedUpgrade as $uid) {
            $this->checkUpgrade($I, $this->userRepository->find($uid));
        }

        $this->checkUpgrade($I, $this->realUpgradeUser);
        $this->checkUpgrade($I, $this->fakeUpgradeUser);

        $this->checkNotModified($I, $this->fakeNonUpgradeUser2);
        $this->checkNotModified($I, $this->nonUpgradeUser);
    }

    private function conditionUserData(\TestSymfonyGuy $I, Usr $user, int $earnings)
    {
        for ($i = 0; $i <= UpgradeReaders::CONDITION_MIN_VISIT; $i++) {
            $in = mktime(0, 0, 0, 1, 1, 2022);
            $out = time(); // ($in + (2 * 60 * UpgradeReaders::CONDITION_MIN_TIME_IN_MINUTE))
            $I->haveInDatabase('BlogUserReport', [
                'UserID' => $user->getId(),
                'BlogPostID' => $i,
                'InTime' => (new \DateTime('@' . $in))->format('Y-m-d H:i:s'),
                'OutTime' => (new \DateTime('@' . $out))->format('Y-m-d H:i:s'),
                'TimeZoneOffset' => 0,
            ]);
        }

        $date = new \DateTime(UpgradeReaders::CONDITION_DATE);
        $date->add(new \DateInterval('P1M'));
        $I->haveInDatabase('QsTransaction', [
            'UserID' => $user->getId(),
            'QsCreditCardID' => 1,
            'Clicks' => 1,
            'Approvals' => 1,
            'ClickDate' => $date->format('Y-m-d H:i:s'),
            'Earnings' => $earnings,
        ]);
    }

    private function checkUpgrade(\TestSymfonyGuy $I, Usr $user): void
    {
        $I->seeInDatabase('Usr', [
            'UserID' => $user->getId(),
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ]);
        $I->seeInDatabase('Cart', ['UserID' => $user->getId()]);

        $expired = new \DateTime(AwPlus6Months::DURATION);
        $expirationDate = $I->grabFromDatabase('Usr', 'PlusExpirationDate',
            ['UserID' => $user->getId()]
        );
        $I->assertEquals($expired->format('Y-m-d'), explode(' ', $expirationDate)[0]);

        $I->seeEmailTo(
            $user->getEmail(),
            $I->grabService('translator')->trans('your-free-upgrade-awplus', [], 'email')
        );
    }

    private function checkNotModified(\TestSymfonyGuy $I, Usr $user): void
    {
        $I->seeInDatabase('Usr', [
            'UserID' => $user->getId(),
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
        ]);

        $I->dontSeeInDatabase('Cart', ['UserID' => $user->getId()]);
    }
}
