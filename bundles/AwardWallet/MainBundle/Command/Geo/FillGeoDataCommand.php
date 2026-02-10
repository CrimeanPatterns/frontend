<?php

namespace AwardWallet\MainBundle\Command\Geo;

use AwardWallet\MainBundle\Entity\Geo\Adapters\UserIPAdapter;
use AwardWallet\MainBundle\Entity\Geo\Adapters\UsrDivisionsAdapter;
use AwardWallet\MainBundle\Entity\UserIP;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtColumn;

class FillGeoDataCommand extends Command
{
    protected static $defaultName = 'aw:fill-geodata';

    private EntityManagerInterface $entityManager;
    private Connection $unbufConnection;
    private GeoLocation $geoLocation;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $unbufConnection,
        GeoLocation $geoLocation
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->unbufConnection = $unbufConnection;
        $this->geoLocation = $geoLocation;
    }

    protected function configure()
    {
        $this
            ->setDescription('fill geo-data (Usr, UserIP) based on geoip database')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'limit updated rows for each table')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'table to update (Usr, UserIP)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $input->getOption('table');

        if (\in_array('Usr', $tables, true)) {
            $this->fillUsr($input, $output);
        }

        if (\in_array('UserIP', $tables, true)) {
            $this->fillUserIP($input, $output);
        }

        return 0;
    }

    private function fillUsr(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->entityManager->getRepository(Usr::class);
        $output->writeln('Processing Usr...');
        $stmt = $this->unbufConnection->executeQuery("select UserID from Usr where IsLastLogonPointSet = 0 and (LastLogonIP is not null or RegistrationIP is not null)");
        $unresolved = 0;
        $corrected = 0;
        $it = stmtColumn($stmt);
        $limit = $input->getOption('limit');

        if ($limit) {
            $it = $it->take((int) $limit);
        }

        $it = $it
            ->onNthAndLast(100, function () {
                $this->entityManager->flush();
                $this->entityManager->clear();
            })
            ->onNthAndLast(1000, function ($processed, $v, $k, $isTotal) use (&$corrected, &$unresolved, $output) {
                $output->writeln(
                    ($isTotal ? '[TOTAL] ' : '')
                    . "Usr: Processed $processed users, corrected: $corrected, unresolved: $unresolved"
                );
            });

        foreach ($it as $userId) {
            /** @var Usr $user */
            $user = $repo->find($userId);

            if (!$user) {
                continue;
            }

            $ip = $user->getLastKnownIp();

            if (StringUtils::isNotEmpty($ip)) {
                $this->geoLocation->updateGeoDataByIp(
                    new UsrDivisionsAdapter($user),
                    $ip
                );

                if ($user->isLastLogonPointSet()) {
                    $corrected++;
                } else {
                    $unresolved++;
                }

                $this->entityManager->persist($user);
            } else {
                $unresolved++;
            }
        }
    }

    private function fillUserIP(InputInterface $input, OutputInterface $output)
    {
        $repo = $this->entityManager->getRepository(UserIP::class);
        $output->writeln('Processing UserIP...');
        $stmt = $this->unbufConnection->executeQuery("select UserIPID from UserIP where IsPointSet = 0");
        $unresolved = 0;
        $corrected = 0;
        $it = stmtColumn($stmt);
        $limit = $input->getOption('limit');

        if ($limit) {
            $it = $it->take((int) $limit);
        }

        $it = $it
            ->onNthAndLast(100, function () {
                $this->entityManager->flush();
                $this->entityManager->clear();
            })
            ->onNthAndLast(1000, function ($processed, $v, $k, $isTotal) use (&$corrected, &$unresolved, $output) {
                $output->writeln(
                    ($isTotal ? '[TOTAL] ' : '')
                    . "UserIP: Processed $processed rows, corrected: $corrected, unresolved: $unresolved"
                );
            });

        foreach ($it as $userIp) {
            /** @var UserIP $userIp */
            $userIp = $repo->find($userIp);

            if (!$userIp) {
                continue;
            }

            $ip = $userIp->getIp();

            if (StringUtils::isNotEmpty($ip)) {
                $this->geoLocation->updateGeoDataByIp(
                    new UserIPAdapter($userIp),
                    $ip
                );

                if ($userIp->isPointSet()) {
                    $corrected++;
                } else {
                    $unresolved++;
                }

                $this->entityManager->persist($userIp);
            } else {
                $unresolved++;
            }
        }
    }
}
