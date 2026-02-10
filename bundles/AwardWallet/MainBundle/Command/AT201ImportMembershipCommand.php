<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AT201ImportMembershipCommand extends Command
{
    private const SPREADSHEET_URL = 'https://docs.google.com/spreadsheets/d/1eZX-7Mlde_Ln2LMnuTc6TwU0FZEjLSd7aRQJzAZ-E64/gviz/tq?tqx=out:csv&sheet=Sheet1';

    /** @var EntityManagerInterface */
    private $em;

    /** @var string */
    private $filePath;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, string $filePath)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->filePath = $filePath;
    }

    protected function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Import Facebook AT201 membership started');
        $this->logger->info('Downloading...');
        exec(sprintf('wget -O %s %s', $this->filePath, self::SPREADSHEET_URL));
        $this->logger->info('Downloaded!');

        $file = fopen($this->filePath, "r");
        $columns = array_flip(fgetcsv($file));
        $userRepo = $this->em->getRepository(Usr::class);

        $count = 0;
        $updated = 0;
        $memberships = [];

        while ($items = fgetcsv($file)) {
            $count++;
            $email = trim($items[7]);
            $facebookLink = trim($items[1]);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->notice(
                    'Invalid email.',
                    ['fullName' => $items[2], 'row' => $count, 'value' => $email]
                );

                continue;
            }

            if (!preg_match("#facebook.com\/(\d+)$#", $facebookLink, $matches)) {
                $this->logger->notice(
                    'Unknown Facebook link format.',
                    ['fullName' => $items[2], 'row' => $count, 'value' => $facebookLink]
                );

                continue;
            }

            /** @var Usr $user */
            $user = $userRepo->findOneBy(['email' => $email]);

            if ($user === null) {
                $this->logger->notice(
                    'Can not identify user.',
                    ['fullName' => $items[2], 'row' => $count, 'value' => $email]
                );

                continue;
            }

            if ($matches[1] === $user->getFacebookUserId()) {
                continue;
            }

            $user->setFacebookUserId($matches[1]);
            $this->em->persist($user);
            $updated++;
        }
        fclose($file);

        $this->em->flush();
        $this->logger->info(sprintf('%s rows processed. %s rows updated. Done!', $count, $updated));

        return 0;
    }
}
