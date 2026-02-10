<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20181023080917 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var EntityManager */
    private $entityManager;

    /** @var BalanceWatchManager */
    private $aauManager;

    /** @var Connection */
    private $unbuffConn;

    /** @var LoggerInterface */
    private $loggerPayment;

    public function setContainer(ContainerInterface $container = null)
    {
        // $this->entityManager = $container->get('doctrine.orm.default_entity_manager');
        // $this->aauManager = $container->get('aw.manager.aau_manager');
        // $this->unbuffConn = $container->get('doctrine.dbal.read_replica_unbuffered_connection');
        // $this->loggerPayment = $container->get('monolog.logger.payment');
    }

    public function up(Schema $schema): void
    {
        /*
        $userRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $count = $this->entityManager->getConnection()->fetchColumn('SELECT COUNT(*) FROM Usr WHERE AccountLevel = ' . ACCOUNT_LEVEL_AWPLUS . ' AND AAUCredits = 0');
        $users = $this->unbuffConn->executeQuery('SELECT UserID FROM Usr WHERE AccountLevel = ' . ACCOUNT_LEVEL_AWPLUS . ' AND AAUCredits = 0');
        $index = 0;
        $creditGift = 1;
        $this->write('Found ' . $count . ' users for AAUCredit gift');
        while ($usr = $users->fetch()) {
            $this->loggerPayment->info('AAUManager credit gift', ['UserID' => $usr['UserID'], 'userAAUCredits' => $creditGift]);
            $u = $userRepository->find($usr['UserID']);
            $u->setBalanceWatchCredits($creditGift);
            $aauCreditTransaction = (new BalanceWatchCreditsTransaction($u, BalanceWatchCreditsTransaction::TYPE_GIFT, $creditGift))->setBalance($creditGift);
            $this->entityManager->persist($aauCreditTransaction);

            if (++$index % 100 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();

                $this->write('Processed ' . $index . ' of ' . $count . ' ...');
            }
        }

        $this->entityManager->flush();
        $this->write('DONE migrations');
        */
    }

    public function down(Schema $schema): void
    {
    }
}
