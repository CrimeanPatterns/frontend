<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Invites;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160317165028 extends AbstractMigration implements ContainerAwareInterface
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
        $sql = "
            select
                i.InvitesID,
                u.Login as Inviter,
                u.UserID as InviterID,
                u2.Login as Invitee,
                u2.UserID as InviteeID,
                i.InviteDate
            from
                Invites i
                join Usr u ON u.UserID = i.InviterID AND u.AccountLevel = 3
                join Usr u2 ON u2.UserID = i.InviteeID AND (u2.OwnedByBusinessID IS NULL OR u2.OwnedByBusinessID <> i.InviterID) AND u2.AccountLevel <> 3
            where
                i.Approved = 1
            order by i.InviteDate desc
        ";
        $invRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Invites::class);

        foreach ($this->connection->query($sql) as $row) {
            /** @var Invites $invite */
            $invite = $invRep->find($row["InvitesID"]);
            $invite->getInviteeid()->setOwnedByBusiness($invite->getInviterid());
        }
        $this->em->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
