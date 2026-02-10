<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\PopularityHandler;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCountriesCommand extends Command
{
    protected static $defaultName = 'aw:fix-countries';

    private EntityManagerInterface $entityManager;
    private Connection $unbufConnection;
    private PopularityHandler $popularityHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $unbufConnection,
        PopularityHandler $popularityHandler
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->unbufConnection = $unbufConnection;
        $this->popularityHandler = $popularityHandler;
    }

    protected function configure()
    {
        $this
            ->setDescription('fill countryid based on geoip');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManager $em */
        $em = $this->entityManager;
        $cn = $this->unbufConnection;
        $popularityHandler = $this->popularityHandler;
        $repo = $this->entityManager->getRepository(Usr::class);

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
                $output->writeln("Processed $processed users, corrected: $corrected, unresolved: $unresolved, flushing...");
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        $output->writeln("Processed $processed users, corrected: $corrected, unresolved: $unresolved, done");

        return 0;
    }
}
