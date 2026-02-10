<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BenchmarkUsersCommand extends Command
{
    private const USERS_COUNT = 500;
    private const URLS = [
        '/account/list',
        '/m/api/data',
        '/user/profile',
        '/timeline/',
        '/timeline/data?showDeleted=0',
    ];
    public static $defaultName = 'aw:test:benchmark-users';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \CurlDriver
     */
    private $curlDriver;
    /**
     * @var string
     */
    private $protoAndHost;
    /**
     * @var Filesystem
     */
    private $fileSystem;
    /**
     * @var ProgressLogger
     */
    private $progress;
    /**
     * @var int
     */
    private $requestCount = 0;
    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        \CurlDriver $curlDriver,
        string $protoAndHost,
        HttpKernelInterface $httpKernel,
        SessionInterface $session,
        UserManager $userManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->curlDriver = $curlDriver;
        $this->protoAndHost = $protoAndHost;
        // parent will fire configure, so fill properties before it
        parent::__construct();
        $this->httpKernel = $httpKernel;
        $this->session = $session;
        $this->userManager = $userManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("starting benchmark on {$input->getOption('host')}");
        $this->fileSystem = new Filesystem();
        $this->progress = new ProgressLogger($this->logger, 5, 30);
        $this->output = $output;

        if ($file = $input->getOption('load-user-list')) {
            $users = explode("\n", file_get_contents($file));
        } else {
            $users = array_unique(array_merge(
                $this->loadUsersWithManyTrips($input->getOption('users')),
                $this->loadUsersWithManyAccounts($input->getOption('users'))
            ));
        }

        if ($file = $input->getOption('save-user-list')) {
            $output->writeln("saving user list to file $file");
            file_put_contents($file, implode("\n", $users));
        }

        $stats = $this->runBenchmark($users, $input->getOption('host'), $input->getOption('http-auth'), $input->getOption('local'));
        $style = new SymfonyStyle($input, $output);
        $this->showStats($stats, $style);
        $this->showMaxTimes($stats, $style, 10);

        $output->writeln("done");

        return 0;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'number of users to test', self::USERS_COUNT)
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'host to run benchmark against', $this->protoAndHost)
            ->addOption('http-auth', null, InputOption::VALUE_REQUIRED, 'http auth in form user:password')
            ->addOption('save-user-list', null, InputOption::VALUE_REQUIRED, 'save user list to this file')
            ->addOption('load-user-list', null, InputOption::VALUE_REQUIRED, 'load user list from this file')
            ->addOption('local', null, InputOption::VALUE_NONE, 'run simulated local http request')
        ;
    }

    private function loadUsersWithManyTrips(int $usersCount): array
    {
        $this->output->writeln("loading users with many trips");

        return $this->connection->executeQuery("
        select  
            u.Login,
            sum(t.Cnt + r.Cnt + l.Cnt + e.Cnt) as Cnt
        from
            Usr u
            left join (select UserID, count(TripID) as Cnt from Trip group by UserID) t on t.UserID = u.UserID
            left join (select UserID, count(ReservationID) as Cnt from Reservation group by UserID) r on r.UserID = u.UserID
            left join (select UserID, count(RentalID) as Cnt from Rental group by UserID) l on l.UserID = u.UserID
            left join (select UserID, count(RestaurantID) as Cnt from Restaurant group by UserID) e on e.UserID = u.UserID
        where 
            u.AccountLevel <> " . ACCOUNT_LEVEL_BUSINESS . "
        group by 
            u.Login
        order by 
            Cnt desc
        limit {$usersCount}")->fetchAll(FetchMode::COLUMN, 0);
    }

    private function loadUsersWithManyAccounts(int $usersCount): array
    {
        $this->output->writeln("loading users with many accounts");

        return $this->connection->executeQuery("
        select 
            Login 
        from 
            Usr 
        where 
            AccountLevel <> " . ACCOUNT_LEVEL_BUSINESS . "
        order by 
            Accounts desc 
        limit {$usersCount}")->fetchAll(FetchMode::COLUMN, 0);
    }

    /**
     * @return array ['/url/1' => [['login' => 'mike', 'responseTime' => 132], ...
     */
    private function runBenchmark(array $users, string $protoAndHost, ?string $httpAuth, bool $local): array
    {
        $this->output->writeln("running benchmark on " . count($users) . " users against {$protoAndHost}");

        if ($local) {
            $this->session->start();
        }
        $result = [];

        foreach ($users as $index => $login) {
            foreach (self::URLS as $url) {
                $this->output->writeln("{$this->requestCount} GET {$url} for {$login}..");
                $this->requestCount++;
                $startTime = microtime(true);

                if ($local) {
                    $this->sendLocalRequest($login, $url);
                } else {
                    $this->sendHttpRequest($this->createFullUrl($login, $protoAndHost, $url), $httpAuth);
                }
                $responseTime = round((microtime(true) - $startTime) * 1000);
                $result[$url][] = [
                    'login' => $login,
                    'responseTime' => $responseTime,
                ];
            }
        }

        return $result;
    }

    private function sendHttpRequest(string $url, ?string $httpAuth)
    {
        $dir = sys_get_temp_dir() . "/log" . bin2hex(random_bytes(8));
        $browser = new \HttpBrowser("dir", $this->curlDriver, $dir);

        if ($httpAuth !== null) {
            $browser->setDefaultHeader('Authorization', 'Basic ' . base64_encode($httpAuth));
        }
        $browser->GetURL($url, [], 180);

        if ($browser->Response['code'] != 200) {
            throw new \Exception("got http {$browser->Response['code']} while loading {$url}, see logs in {$dir}");
        }
        $this->fileSystem->remove($dir);
    }

    private function createFullUrl(string $login, string $protoAndHost, string $url): string
    {
        return $protoAndHost . $url . '?_switch_user=' . urlencode($login);
    }

    /**
     * @param array $stats - ['/url/1' => [['login' => 'mike', 'responseTime' => 132], ...
     */
    private function showStats(array $stats, StyleInterface $style)
    {
        $style->title("Stats by Url");
        $style->table(
            ["Url", "Avg", "Min", "Max"],
            it($stats)
                ->map(function (array $responses) {
                    $times = array_column($responses, 'responseTime');

                    return [
                        'Avg' => round(array_sum($times) / count($times)),
                        'Min' => min($times),
                        'Max' => max($times),
                    ];
                })
                ->mapIndexed(function (array $value, $key) { // как такое сделать?
                    return array_merge([
                        'Url' => $key,
                    ], $value);
                })
                ->toArray()
        );
    }

    /**
     * @param array $stats - ['/url/1' => [['login' => 'mike', 'responseTime' => 132], ...
     */
    private function showMaxTimes(array $stats, StyleInterface $style, int $count)
    {
        foreach ($stats as $url => $responses) {
            $style->title("Max $count response times for $url");
            $style->table(
                ["User", "Time"],
                it($responses)
                    ->usort(function ($a, $b) {
                        return $b['responseTime'] <=> $a['responseTime'];
                    })
                    ->slice(0, $count)
                    ->map(function ($respone) {
                        return [$respone['login'], $respone['responseTime']];
                    })
                    ->toArray()
            );
        }
    }

    private function sendLocalRequest($login, $url)
    {
        $this->userManager->loadToken($this->userManager->findUser($login, false), false, UserManager::LOGIN_TYPE_ADMINISTRATIVE);
        // see vendor/symfony/symfony/web/Symfony/Component/Security/Http/Firewall/ContextListener.php
        $this->session->set("_security_secured_area", serialize($this->tokenStorage->getToken()));

        $request = Request::create($this->protoAndHost . $url, 'GET', [], [$this->session->getName() => $this->session->getId()]);
        $request->setSession($this->session);
        $response = $this->httpKernel->handle($request);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("got http {$response->getStatusCode()} while loading $url for $login");
        }
    }
}
