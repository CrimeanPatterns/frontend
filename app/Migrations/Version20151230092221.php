<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151230092221 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->em = $container->get('doctrine.orm.default_entity_manager');
    }

    public function up(Schema $schema): void
    {
        return;
        $sql = "
          SELECT
              u.UserID
          FROM Usr u
          INNER JOIN BusinessSubscription s ON u.UserID = s.UserID
          WHERE
              u.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . "
        ";
        $userRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        foreach ($this->connection->query($sql) as $row) {
            \SubscriptionManager::reset();
            $subscriptionManager = \SubscriptionManager::getInstance(true);
            $subscriptionManager->setUser($row['UserID'])
                ->Init();
            $curr = $subscriptionManager->getCurrentSubscriptionInfo();
            $subs = $subscriptionManager->getSubscription();
            $balance = round(floatval($curr['Balance']), 2);
            $discount = $curr['Discount'];
            /** @var Usr $user */
            $user = $userRep->find($row["UserID"]);
            $user->getBusinessInfo()->setBalance($balance);
            $user->getBusinessInfo()->setDiscount($discount);

            if ($subs->isFreeSubscription()) {
                $user->getBusinessInfo()->setTrialEndDate(new \DateTime($subs->getEndDate()));
            } else {
                $user->getBusinessInfo()->setTrialEndDate(null);
            }
        }
        $this->em->flush();

        // Abroaders and Bya
        $this->addSql("
            UPDATE BusinessInfo SET Balance = 0, Discount = 100 WHERE UserID IN (221732, 116000)
        ");
        $this->addSql("
            UPDATE AbBookerInfo SET Discount = 100 WHERE UserID IN (221732, 116000)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
