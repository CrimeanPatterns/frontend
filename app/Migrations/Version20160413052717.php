<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\PopularityHandler;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20160413052717 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function up(Schema $schema): void
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $cn = $this->container->get("doctrine.dbal.unbuffered_connection");
        $popularityHandler = $this->container->get(PopularityHandler::class);
        $repo = $this->container->get("aw.repository.usr");

        $processed = 0;
        $corrected = 0;
        $unresolved = 0;
        $q = $cn->executeQuery("select UserID from Usr where CountryID is null and (LastLogonIP is not null or RegistrationIP is not null)");
        $q->execute();

        while ($userId = $q->fetchColumn()) {
            /** @var Usr $user */
            $user = $repo->find($userId);

            if ($user->getLastKnownIp()) {
                $popularityHandler->defineCountry($user, true);
                $corrected++;
                $em->persist($user);

                if (empty($user->getCountryid())) {
                    $unresolved++;
                }
            }
            $processed++;

            if (($processed % 100) == 0) {
                $this->write("Processed $processed users, corrected: $corrected, unresolved: $unresolved, flushing...");
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        $this->write("Processed $processed users, corrected: $corrected, unresolved: $unresolved, done");
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
