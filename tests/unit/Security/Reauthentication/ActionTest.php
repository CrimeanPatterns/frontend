<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 * @group security
 */
class ActionTest extends BaseTest
{
    public function testGetChangeEmailAction()
    {
        $this->assertEquals('change-email', Action::getChangeEmailAction());
    }

    public function testGetChangePasswordAction()
    {
        $this->assertEquals('change-pass', Action::getChangePasswordAction());
    }

    public function testGetRevealAccountPasswordAction()
    {
        $this->assertEquals('reveal-account-123-password', Action::getRevealAccountPasswordAction(123));
    }

    public function testGetDeleteAccountAction()
    {
        $this->assertEquals('delete-aw-account', Action::getDeleteAccountAction());
    }

    public function testGet2FactSetupAction()
    {
        $this->assertEquals('2fact-setup', Action::get2FactSetupAction());
    }

    public function testGet2FactCancelAction()
    {
        $this->assertEquals('2fact-cancel', Action::get2FactCancelAction());
    }

    public function testGetEnableAutoLoginAction()
    {
        $this->assertEquals('enable-account-123-autologin', Action::getEnableAutoLoginAction(123));
    }

    public function testBackupPasswordsAction()
    {
        $this->assertEquals('backup-passwords', Action::getBackupPasswordsAction());
    }

    public function testValidateAction()
    {
        $this->assertFalse(Action::validateAction('xxx'));
        $this->assertFalse(Action::validateAction('change_email'));
        $this->assertFalse(Action::validateAction('reveal-account-password'));
        $this->assertTrue(Action::validateAction('reveal-account-100500-password'));
        $this->assertTrue(Action::validateAction('enable-account-100500-autologin'));
        $this->assertTrue(Action::validateAction('change-email'));
        $this->assertTrue(Action::validateAction('change-pass'));
        $this->assertTrue(Action::validateAction('delete-aw-account'));
        $this->assertTrue(Action::validateAction('2fact-setup'));
        $this->assertTrue(Action::validateAction('2fact-cancel'));
        $this->assertTrue(Action::validateAction('backup-passwords'));
    }
}
