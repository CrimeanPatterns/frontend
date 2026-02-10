<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\InviteUser\InviteUserHelper;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class InviteUserHelperTest extends BaseContainerTest
{
    private ?Usr $user;
    private ?InviteUserHelper $helper;

    public function _before()
    {
        parent::_before();
        $userId = $this->aw->createAwUser();
        $this->user = $this->em->getRepository(Usr::class)->find($userId);
        $this->helper = new InviteUserHelper(
            $this->makeEmpty(AwTokenStorageInterface::class, ['getUser' => $this->user]),
            $this->em,
            $this->makeEmpty(Mailer::class, ['send' => true]),
            $this->em->getRepository(Invites::class)
        );
    }

    public function _after()
    {
        $this->user = null;
        $this->helper = null;
        parent::_after();
    }

    /**
     * Отправляет письмо с учётной записи с неподтверждённым e-mail.
     */
    public function testSendWhenEmailNotVerified()
    {
        $this->user->setEmailverified(EMAIL_UNVERIFIED);
        $response = $this->helper->send('guest@awardwallet.com');

        $this->assertEquals(InviteUserHelper::STATUS_EMAIL_NOT_VERIFIED, $response);
    }

    /**
     * Отправляет письмо с учётной записи с подтверждённым e-mail.
     */
    public function testSendWhenEmailVerified()
    {
        $this->user->setEmailverified(EMAIL_VERIFIED);
        $response = $this->helper->send('guest@awardwallet.com');

        $this->assertEquals(InviteUserHelper::STATUS_SENT, $response);

        $invite = $this->em->getRepository(Invites::class)->findOneBy([
            'inviterid' => $this->user,
            'email' => 'guest@awardwallet.com',
        ]);
        $this->assertNotNull($invite);
    }

    /**
     * Отправляет письмо на некорретный e-mail адрес.
     */
    public function testSendToIncorrectEmail()
    {
        $this->user->setEmailverified(EMAIL_VERIFIED);
        $response = $this->helper->send('');

        $this->assertEquals(InviteUserHelper::STATUS_NOT_SENT, $response);
    }
}
