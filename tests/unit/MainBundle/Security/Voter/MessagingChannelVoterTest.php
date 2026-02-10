<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelAccessCheckerInterface;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelVoter;
use AwardWallet\MainBundle\Security\Voter\Subject\MessagingChannel;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelVoter
 */
class MessagingChannelVoterTest extends BaseTest
{
    /**
     * @covers ::canRead
     */
    public function testAbsenceOfRegisteredCheckersShouldDenyAccess()
    {
        $voter = new MessagingChannelVoter(
            $this->prophesize(ContainerInterface::class)->reveal(),
            []
        );
        $this->assertFalse($voter->canRead(
            $this->createToken(),
            new MessagingChannel('some_channel')
        ));
    }

    /**
     * @covers ::canRead
     */
    public function testSuccessResultOfOneCheckerFromMultipleCheckerShouldAllowAccess()
    {
        $voter = new MessagingChannelVoter(
            $this->prophesize(ContainerInterface::class)->reveal(),
            [
                $this->prophesize(MessagingChannelAccessCheckerInterface::class)
                    ->checkChannelAuth(Argument::any())
                    ->willReturn(false)
                    ->shouldBeCalledOnce()
                    ->getObjectProphecy()
                    ->reveal(),

                $this->prophesize(MessagingChannelAccessCheckerInterface::class)
                    ->checkChannelAuth(Argument::any())
                    ->willReturn(true)
                    ->shouldBeCalledOnce()
                    ->getObjectProphecy()
                    ->reveal(),

                $this->prophesize(MessagingChannelAccessCheckerInterface::class)
                    ->checkChannelAuth(Argument::any())
                    ->shouldNotBeCalled()
                    ->getObjectProphecy()
                    ->reveal(),
            ]
        );
        $this->assertTrue($voter->canRead(
            $this->createToken(),
            new MessagingChannel('some_channel')
        ));
    }

    protected function createToken(?Usr $user = null): TokenInterface
    {
        return new PostAuthenticationGuardToken($user ?: new Usr(), 'provider', []);
    }
}
