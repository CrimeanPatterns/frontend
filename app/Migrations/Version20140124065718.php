<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Groupuserlink;
use AwardWallet\MainBundle\Entity\Sitegroup;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140124065718 extends AbstractMigration implements ContainerAwareInterface
{
    public const GROUP_NAME = 'staff:email_parser';

    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var Connection
     */
    protected $connection;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->em = $container->get('doctrine.orm.entity_manager');
    }

    public function up(Schema $schema): void
    {
        $group = new Sitegroup();
        $group->setGroupname(self::GROUP_NAME);
        $group->setDescription('Parse emails');
        $this->em->persist($group);
        $this->write("created staff:email group");
        $users = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        foreach (['SiteAdmin', 'vladimir', 'AMalutin', 'IAlabuzheva'] as $login) {
            $user = $users->findOneBy(['login' => $login]);

            if (!empty($user)) {
                $this->write("giving permissions to $login");
                $link = new Groupuserlink();
                $link->setUserid($user);
                $link->setSitegroupid($group);
                $this->em->persist($link);
            }
        }
        $this->em->flush();
    }

    public function down(Schema $schema): void
    {
        $this->em->getConnection()->executeQuery("delete from SiteGroup where GroupName = '" . self::GROUP_NAME . "'");
    }
}
