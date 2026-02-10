<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchRefund;
use AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchStart;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20190314101112 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $accountRepository = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

        $transaction = $entityManager->getConnection()->fetchAll('
            SELECT BusinessTransactionID, SourceID, SourceDesc
            FROM BusinessTransaction
            WHERE `Type` IN (' . BalanceWatchStart::TYPE . ', ' . BalanceWatchRefund::TYPE . ')');

        foreach ($transaction as $t) {
            if (empty($t['SourceID'])) {
                continue;
            }

            $account = $accountRepository->find($t['SourceID']);

            if (empty($account)) {
                continue;
            }

            $sourceDesc = explode(':', $t['SourceDesc'], 2);
            $jsonSource = [
                'provider' => $account->getProviderid()->getShortname(),
                'login' => $account->getLogin(),
                'username' => $account->getUser()->getFullName(),
            ];

            if (2 === \count($sourceDesc)) {
                $jsonSource['payerUid'] = $sourceDesc[0];
            }

            $entityManager->getConnection()->executeUpdate(
                'UPDATE BusinessTransaction SET SourceDesc = ? WHERE BusinessTransactionID = ?',
                [\json_encode($jsonSource), $t['BusinessTransactionID']],
                [\PDO::PARAM_STR, \PDO::PARAM_INT]
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
