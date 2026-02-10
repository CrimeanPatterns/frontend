<?php

/**
 * @group frontend-unit
 */
class InviteLockerTest extends \AwardWallet\Tests\Unit\BaseContainerTest
{
    /**
     * @var \AwardWallet\MainBundle\Security\InviteLocker
     */
    protected $locker;

    public function _before()
    {
        parent::_before();
        $this->locker = $this->container->get('aw.security.invite_locker')->init('test@test.ru');
        $this->locker->reset();
    }

    public function testIpLock()
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertTrue($this->locker->checkIp() == null);
        }
        $this->assertFalse($this->locker->checkIp() == null);
    }

    public function testLoginLock()
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertTrue($this->locker->checkLogin() == null);
        }
        $this->assertFalse($this->locker->checkLogin() == null);
    }

    public function testEmailLock()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($this->locker->checkEmail() == null);
        }
        $this->assertFalse($this->locker->checkEmail() == null);
    }
}
