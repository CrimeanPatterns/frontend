<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20140502152132 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var UsrRepository
     */
    private $users;
    /**
     * @var EntityManager
     */
    private $em;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->users = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->em = $container->get('doctrine')->getManager();
    }

    public function up(Schema $schema): void
    {
        $businesses = $this->users->findBy(['accountlevel' => ACCOUNT_LEVEL_BUSINESS]);

        foreach ($businesses as $business) {
            /** @var Usr $business */
            $business->setLogin($this->users->createLogin($business->getUserid(), $business->getCompany()));
            $this->write("set name {$business->getLogin()} for business {$business->getUserid()}, {$business->getCompany()}");
            $this->em->persist($business);
            $this->em->flush();
        }
    }

    public function down(Schema $schema): void
    {
        $this->write("this migration is not reversible");
    }
}
