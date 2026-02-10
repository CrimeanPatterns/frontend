<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixLocalPasswordsKeyCommand extends Command
{
    protected static $defaultName = 'aw:fix-local-passwords-key';

    private Connection $connection;
    private $localPasswordsKey;
    private $localPasswordsKeyOld;

    public function __construct(
        Connection $connection,
        $localPasswordsKey,
        $localPasswordsKeyOld
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->localPasswordsKeyOld = $localPasswordsKeyOld;
    }

    public function configure()
    {
        $this
            ->setDescription("Recipher items encoded with old local_passwords_key with current one")
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'run, otherwise check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $localPasswordsKey = $this->localPasswordsKey;
        $localPasswordsKeyOld = $this->localPasswordsKeyOld;
        $force = $input->getOption("force");
        $conn = $this->connection;
        $update = $conn->prepare("update Account set AuthInfo = :AuthInfo where AccountID = :AccountID");

        $io->section("AuthInfo");

        $rows = $conn->executeQuery("select AccountID, AuthInfo from Account where AuthInfo is not null and AuthInfo <> ''")->fetchAll(\PDO::FETCH_ASSOC);
        $old = 0;
        $new = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $bin = @base64_decode($row['AuthInfo']);
            $decoded = @json_decode(@AESDecode($bin, $localPasswordsKey));

            if (empty($decoded)) {
                $decoded = @json_decode(@AESDecode($bin, $localPasswordsKeyOld));

                if (!empty($decoded)) {
                    $old++;

                    if ($force) {
                        $io->text("recoding account {$row['AccountID']}");
                        $update->execute(["AuthInfo" => base64_encode(AESEncode(json_encode($decoded), $localPasswordsKey)), "AccountID" => $row['AccountID']]);
                    } else {
                        $io->text("account should be recoded: {$row['AccountID']}");
                    }
                } else {
                    $io->warning("could not decode account {$row['AccountID']}");
                    $errors++;
                }
            } else {
                $new++;
            }
        }

        $message = "done, processed: " . count($rows) . ", old: $old, new: $new, errors: $errors";

        if ($errors > 0) {
            $io->error($message);
        } else {
            $io->success($message);
        }

        return 0;
    }
}
