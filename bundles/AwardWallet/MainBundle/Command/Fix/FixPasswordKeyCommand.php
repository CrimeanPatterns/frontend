<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Common\PasswordCrypt\CryptException;
use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FixPasswordKeyCommand extends Command
{
    public static $defaultName = 'aw:fix-password-key';

    private Connection $connection;

    private PasswordDecryptor $passwordDecryptor;

    private PasswordEncryptor $passwordEncryptor;

    public function __construct(Connection $connection, PasswordDecryptor $passwordDecryptor, PasswordEncryptor $passwordEncryptor)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->passwordDecryptor = $passwordDecryptor;
        $this->passwordEncryptor = $passwordEncryptor;
    }

    public function configure()
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $accounts = $this->connection->executeQuery("select AccountID, Pass from Account where SavePassword = "
        . SAVE_PASSWORD_DATABASE . " and PassChangeDate >= '2021-09-07' and PassChangeDate <= '2021-09-08'
        and Pass <> ''")->fetchAllKeyValue();

        $localKeyDecryptor = new PasswordDecryptor(<<<EOF
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCloUCnovvNGQlIfJ0sL/RTIllp
iIITixa27bwSO+qp25ArFTnNBiaDsYGxAWKB1jfvSNfUmZins4YgtTtKgNiRNfR4
B+1hHNw2fGkglRx1YyZcFMiDuof98tcDEhdVd24muMI90I+X1tbJwkPmGZSpI0GW
47e1Yx1VcTVNlwoetQIDAQAB
-----END PUBLIC KEY-----
EOF
        );

        $output->writeln("loaded " . count($accounts) . " accounts");

        it($accounts)
            ->applyIndexed(function (string $encodedPassword, int $accountId) use ($localKeyDecryptor, $output, $input) {
                try {
                    $this->passwordDecryptor->decrypt($encodedPassword);
                    //                    throw new CryptException("locally decrypted");
                } catch (CryptException $exception) {
                    try {
                        $password = $localKeyDecryptor->decrypt($encodedPassword);

                        if (empty($password)) {
                            throw new CryptException("empty password");
                        }

                        $output->writeln("successfully recrypted {$accountId}: $password");

                        if ($input->getOption('apply')) {
                            $this->connection->executeStatement(
                                "update Account set Pass = ? where AccountID = ?",
                                [$this->passwordEncryptor->encrypt($password), $accountId]
                            );
                        }
                    } catch (CryptException $exception) {
                    }
                }
            })
        ;

        $output->writeln("done");

        return 0;
    }
}
