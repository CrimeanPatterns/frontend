<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixIpCommand extends Command
{
    protected static $defaultName = 'aw:fix-ip';

    /** @var Connection */
    private $conn;

    /** @var EntityManager */
    private $em;

    /** @var SymfonyStyle */
    private $io;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $unbufConnection
    ) {
        parent::__construct();
        $this->em = $entityManager;
        $this->conn = $unbufConnection;
    }

    public function configure()
    {
        $this
            ->setDescription('Correction of invalid ip addresses in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->usr();
        $this->userip();

        return 0;
    }

    protected function getFilteredIp(string $ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $ip, $matchIp)) {
            return $matchIp[0];
        }

        return null;
    }

    /**
     * Table Usr.
     */
    private function usr()
    {
        $userRep = $this->em->getRepository(Usr::class);

        $users = $this->conn->executeQuery('
            SELECT UserID FROM Usr
            WHERE
                    RegistrationIP IS NOT NULL
                OR  LastLogonIP    IS NOT NULL 
        ');

        $users->execute();
        $processed = $corrected = 0;

        while ($userId = $users->fetchColumn()) {
            /** @var Usr $user */
            $user = $userRep->find($userId);
            $needFix = false;

            if (null !== ($ip = $user->getRegistrationip())) {
                $filteredIp = $this->getFilteredIp($ip);

                if ($filteredIp !== $user->getRegistrationip()) {
                    $user->setRegistrationip($filteredIp);
                    $needFix = true;
                }
            }

            if (null !== ($ip = $user->getLastlogonip())) {
                $filteredIp = $this->getFilteredIp($ip);

                if ($filteredIp !== $user->getLastlogonip()) {
                    $user->setLastlogonip($filteredIp);
                    $needFix = true;
                }
            }

            if ($needFix) {
                $this->em->persist($user);
                ++$corrected;
            }

            if (0 === (++$processed % 100)) {
                $this->em->flush();
                $this->em->clear();
                $this->io->writeln('Usr processed ' . $processed . ' users, corrected: ' . $corrected . ', flushing...');
            }
        }

        $this->em->flush();
        $this->io->success('Usr done, processed ' . $processed . ' users, corrected: ' . $corrected);
    }

    /**
     * Table UserIP.
     */
    private function userip()
    {
        $userIps = $this->conn->fetchAll('SELECT UserIPID, IP FROM UserIP');
        $processed = $corrected = 0;

        foreach ($userIps as $userIp) {
            $filteredIp = $this->getFilteredIp($userIp['IP']);

            if (0 === (++$processed % 100)) {
                $this->io->writeln('UserIP processed ' . $processed . ' ip, corrected ' . $corrected . ', info...');
            }

            if ($filteredIp !== $userIp['IP']) {
                $this->conn->update('UserIP', ['IP' => $filteredIp], ['UserIPID' => $userIp['UserIPID']]) ? ++$corrected : null;
                ++$corrected;
            }
        }
        $this->io->success('UserIP done, processed ' . $processed . ' ip, corrected: ' . $corrected);
    }
}
