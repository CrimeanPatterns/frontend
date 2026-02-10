<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BusinessTransaction\BusinessTransactionManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160209222111 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var BusinessTransactionManager
     */
    protected $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->em = $container->get('doctrine.orm.default_entity_manager');
        $this->manager = $container->get('aw.manager.business_transaction');
    }

    public function up(Schema $schema): void
    {
        $sql = "
          SELECT
              u.UserID
          FROM Usr u
          INNER JOIN BusinessInfo s ON u.UserID = s.UserID
          WHERE
              u.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . "
        ";
        $userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        foreach ($this->connection->query($sql) as $row) {
            /** @var Usr $user */
            $user = $userRep->find($row["UserID"]);
            $balance = $user->getBusinessInfo()->getBalance();

            if ($balance > 0) {
                $user->getBusinessInfo()->setBalance(0);
                $this->manager->setBusiness($user);
                $this->manager->addPayment($balance);
            }
        }
        $this->em->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
