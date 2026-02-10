<?php
/**
 * Created by PhpStorm.
 * User: amalutin
 * Date: 23.11.16
 * Time: 16:16.
 */

namespace AwardWallet\MainBundle\Command;

use AppKernel;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ParsedEmailFormatCommand extends Command
{
    protected static $defaultName = 'aw:email-parsing:formats';

    private Connection $connection;
    private KernelInterface $kernel;
    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        KernelInterface $kernel,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Collects stats about currently parsed email formats and writes them to db');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /* @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connection;
        /* @var AppKernel $kernel */
        $kernel = $this->kernel;
        /* @var \Monolog\Logger $logger */
        $logger = $this->logger;
        $pathTemplate = $kernel->getProjectDir() . '/engine/%s/Email/*.php';
        $classTemplate = '\\AwardWallet\\Engine\\%s\\Email\\%s';
        $connection->executeUpdate('update EmailParsingFormat set Updated = 0');
        $query = $connection->executeQuery('select ProviderID, Code from Provider');
        $ins = $upd = $total = 0;
        $totalLang = [];

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $num = 0;
            $languages = [];

            foreach (glob(sprintf($pathTemplate, strtolower($row['Code']))) as $path) {
                $class = sprintf($classTemplate, strtolower($row['Code']), substr(basename($path), 0, -4));

                if (!class_exists($class) || !$this->isChecker($class)) {
                    continue;
                }
                /* @var \TAccountChecker $class */
                $val = $class::getEmailTypesCount();

                if (!is_int($val) || $val < 0) {
                    $logger->error(sprintf('ParsedEmailFormatCommand: invalid types count (%s) in %s', json_encode($val), $class));
                } else {
                    $num += $val;
                }
                $val = $class::getEmailLanguages();

                if (!is_array($val) || count($val) !== count(array_filter($val, function ($code) {return preg_match('/^[a-z]{2}$/', $code) > 0; }))) {
                    $logger->error(sprintf('ParsedEmailFormatCommand: invalid languages (%s) in %s', json_encode($val), $class));
                } else {
                    $languages = array_unique(array_merge($languages, $val));
                }
            }

            if ($num > 0 && count($languages) > 0) {
                if ($connection->executeQuery('select 1 from EmailParsingFormat where ProviderID = ?', [$row['ProviderID']], [\PDO::PARAM_INT])->fetchColumn() !== false) {
                    $connection->update('EmailParsingFormat', ['Count' => $num, 'Languages' => implode(',', $languages), 'Updated' => 1], ['ProviderID' => $row['ProviderID']], [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]);
                    $upd++;
                } else {
                    $connection->insert('EmailParsingFormat', ['Count' => $num, 'Languages' => implode(',', $languages), 'Updated' => 1, 'ProviderID' => $row['ProviderID']], [\PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]);
                    $ins++;
                }
                $total += $num;
                $totalLang = array_unique(array_merge($totalLang, $languages));
            }
        }
        $del = $connection->delete('EmailParsingFormat', ['Updated' => 0], [\PDO::PARAM_INT]);
        $output->writeln(sprintf('Total: %d, Languages: %d, updated: %d, inserted: %d, deleted: %d', $total, count($totalLang), $upd, $ins, $del));

        return 0;
    }

    protected function isChecker($class)
    {
        $reflection = new \ReflectionClass($class);

        if (false === $reflection) {
            return false;
        }

        if (!$reflection->hasMethod('ParsePlanEmail') || 'TAccountChecker' == $reflection->getMethod('ParsePlanEmail')->class) {
            return false;
        }

        do {
            $name = $reflection->getName();

            if ('TAccountChecker' == $name) {
                return true;
            }
            $reflection = $reflection->getParentClass();
        } while (false !== $reflection);

        return false;
    }
}
