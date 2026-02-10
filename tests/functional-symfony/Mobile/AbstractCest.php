<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\UserSteps;
use Codeception\Module\Aw;

abstract class AbstractCest
{
    public const TRANS_VALUE_BLANK = 'This value should not be blank.';
    public const TRANS_MISSING_LOCAL_PASSWORD = 'You opted to save the password for this award program locally, this device does not have it stored.';

    public const HOST = '127.0.0.1';

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var UserSteps
     */
    protected $userSteps;

    /**
     * @var AccountSteps
     */
    protected $accountSteps;

    public function _before(\TestSymfonyGuy $I)
    {
        $scenario = $I->grabScenarioFrom($I);
        $this->userSteps = new UserSteps($scenario);
        $this->accountSteps = new AccountSteps($scenario);

        $I->resetLockout('check_login', self::HOST);
        $I->resetLockout('check_email', self::HOST);
        $I->resetLockout('forgot', self::HOST);
        $I->resetLockout('connection_search', self::HOST);
        $I->resetLockout('ip', self::HOST);
        $I->resetLockout('ip');
        $I->resetLockout('password', Aw::DEFAULT_PASSWORD);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userSteps =
        $this->accountSteps = null;
    }

    /**
     * @param string $loginPrefix
     * @param string $password
     * @param bool $staff
     * @return int
     */
    protected function createUserAndLogin(\TestSymfonyGuy $I, $loginPrefix = 'foobar-', $password = 'userpass', array $userFields = [], $staff = false)
    {
        $this->userId = $this->userSteps->createAwUser(
            $login = $loginPrefix . StringHandler::getRandomCode(10),
            $password,
            $userFields,
            $staff
        );

        $I->sendGET('/m/api/login_status?_switch_user=' . $login);
        $I->saveCsrfToken();

        return $this->userId;
    }
}
