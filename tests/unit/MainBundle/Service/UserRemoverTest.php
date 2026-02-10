<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserDelete;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Manager\LegacySchemaManagerFactory;
use AwardWallet\MainBundle\Scanner\MailboxManager;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\UserRemover;
use AwardWallet\Tests\Modules\Utils\Prophecy\ArgumentExtended as Argument;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Service\UserRemover
 */
class UserRemoverTest extends BaseTest
{
    /**
     * @covers ::deleteUser
     */
    public function testDeleteUser()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning(
                "Deleted AwardWallet Account",
                Argument::containsArray(['UserID' => 100500])
            )
            ->shouldBeCalledOnce();

        $repository = $this->prophesize(UsrRepository::class);
        $repository
            ->getPaymentStatsByUser(100500)
            ->willReturn([
                'PaidOrders' => 1,
                'LifetimeContribution' => 20,
            ]);
        $repository
            ->findBy(Argument::any())
            ->willReturn([]);

        $entityManager = $this->prophesize(EntityManager::class);

        $connection = $this->prophesize(Connection::class);
        $connection->fetchOne(Argument::any())->willReturn(5);
        $connection->fetchAssociative(Argument::any())->willReturn([
            'sumApprovals' => 2,
            'sumEarnings' => 30,
        ]);
        $entityManager
            ->getConnection()
            ->willReturn($connection->reveal());
        $entityManager
            ->getRepository(Argument::any())
            ->willReturn($repository->reveal());
        $entityManager->persist(Argument::any())->willReturn()->shouldBeCalled();
        $entityManager->flush()->willReturn()->shouldBeCalled();

        $user = $this->prophesize(Usr::class);
        $user->getId()->willReturn(100500);
        $user->isUsGreeting()->willReturn(false);
        $user->getFirstname()->willReturn('firstName');
        $user->getLastname()->willReturn('lastName');
        $user->getCreationdatetime()->willReturn(new \DateTime('@' . (time() - 86400)));
        $user->getEmail()->willReturn('delete.test@awardwallet.com');
        $user->getValidMailboxesCount()->willReturn(1);
        $user->getCountryid()->willReturn(null);
        $user->getCamefrom()->willReturn(null);
        $user->getReferer()->willReturn('');
        $user = $user->reveal();

        $counter = $this->prophesize(Counter::class);
        $counter
            ->getTotalAccounts(Argument::exact(100500))
            ->willReturn(30);
        $counter
            ->getTotalItineraries(Argument::exact(100500))
            ->willReturn(100);

        $mailer = $this->prophesize(Mailer::class);
        $mailer
            ->getMessageByTemplate(Argument::that(function ($template) use ($user) {
                /** @var UserDelete $template */
                $this->assertInstanceOf(UserDelete::class, $template);
                $this->assertSame($user, $template->getUser());
                $this->assertEquals('to@aw.com', $template->getEmail());
                $this->assertEquals(30, $template->accounts);
                $this->assertEquals(100, $template->trips);
                $this->assertEquals(1, $template->pays);
                $this->assertEquals(20, $template->lifetimeContribution);
                $this->assertEquals('somereason', $template->reason);

                return true;
            }))
            ->willReturn($message = new Message());
        $mailer
            ->send(Argument::exact($message))
            ->shouldBeCalledOnce();
        $mailer
            ->getEmail('support')
            ->willReturn('to@aw.com');

        $schema = $this->prophesize(\TSchemaManager::class);
        $schema
            ->DeleteRow('Usr', 100500, true)
            ->shouldBeCalledOnce();

        $schemaFactory = $this->prophesize(LegacySchemaManagerFactory::class);
        $schemaFactory
            ->make()
            ->willReturn($schema->reveal());

        $mailboxManager = $this->prophesize(MailboxManager::class);
        $mailboxManager
            ->deleteAllUserMailboxes(Argument::exact($user));

        $planFileManager = $this->prophesize(PlanFileManager::class);
        $itineraryFileManager = $this->prophesize(ItineraryFileManager::class);
        $itineraryFileManager
            ->removeAllFilesByUser($user->getId())->willReturn([]);

        $recurringManager = $this->prophesize(RecurringManager::class);
        $recurringManager
            ->cancelRecurringPayment(Argument::exact($user), Argument::exact(false), Argument::exact(true))->willReturn(true);

        /** @var UserRemover $userRemover */
        $userRemover = $this->makeProphesizedMuted(UserRemover::class, [
            LoggerInterface::class => $logger->reveal(),
            Mailer::class => $mailer->reveal(),
            Counter::class => $counter->reveal(),
            EntityManagerInterface::class => $entityManager->reveal(),
            LegacySchemaManagerFactory::class => $schemaFactory->reveal(),
            MailboxManager::class => $mailboxManager->reveal(),
            PlanFileManager::class => $planFileManager->reveal(),
            ItineraryFileManager::class => $itineraryFileManager->reveal(),
            RecurringManager::class => $recurringManager->reveal(),
        ]);

        $userRemover->deleteUser($user, 'somereason');
    }
}
