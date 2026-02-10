<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Parameter;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\CreditCards\QsTransactionData;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Statistics
{
    public const ALLOW_VIEW = ['business', 'support', 'dev'];
    public const DETAIL_FIELDS = ['commits', 'files', 'inserted', 'deleted'];
    public array $extendMessages = [];

    private LoggerInterface $logger;
    private ManagerRegistry $doctrine;
    private QsTransactionData $qsTransactionData;
    private array $gitModules = [];
    private string $affiliateStatsPath;
    private LocalizeService $localizeService;
    private Connection $conn;
    private array $message = [];
    /** @var \DateTime */
    private $dateAfter;
    /** @var \DateTime */
    private $dateBefore;

    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $doctrine,
        QsTransactionData $qsTransactionData,
        array $gitModules,
        string $affiliateStatsPath,
        LocalizeService $localizeService
    ) {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->qsTransactionData = $qsTransactionData;
        $this->gitModules = $gitModules;
        $this->affiliateStatsPath = $affiliateStatsPath;
        $this->localizeService = $localizeService;
        $this->conn = $doctrine->getConnection();
        $this->dateAfter = new \DateTime("yesterday");
        $this->setDateBefore();
    }

    public function getOverallStat(Request $request)
    {
        $formatter = new \NumberFormatter($request->getLocale(), \NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        $programs = $this->doctrine->getRepository(Provider::class)->getLPCount($_SERVER['DOCUMENT_ROOT']);
        $users = intval($this->doctrine->getRepository(Usr::class)->getUsersCount() / 1000) * 1000;
        $miles = bcdiv($this->doctrine->getRepository(Parameter::class)->getMilesCount(), 1000 * 1000 * 1000, 1);
        $money = $miles * 20 / 1000;

        return [
            'programs' => [(int) $programs, $formatter->format($programs)],
            'members' => [(int) $users, $formatter->format($users), $this->localizeService->formatNumberShort($users, 0)],
            'miles' => [(int) $miles, $formatter->format($miles)],
            'money' => [(int) $money, $formatter->format($money)],
        ];
    }

    /**
     * Collecting all data, based on the method from ALLOW_VIEW.
     *
     * @param array|null $views
     * @throws \BadMethodCallException
     */
    public function fetchAll($views = null): array
    {
        !empty($views) ?: $views = self::ALLOW_VIEW;
        is_array($views) ?: $views = [$views];

        foreach ($views as $view) {
            $method = 'fetch' . ucfirst($view);

            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException('Unsupported type view: "' . $view . '"');
            }
            $this->combineData($this->$method());
        }

        return $this->message;
    }

    /**
     * @throws \Exception
     */
    public function fetchBusiness(): array
    {
        $totalUsers = (int) $this->conn->executeQuery('SELECT COUNT(*) FROM Usr')->fetchColumn();

        $registeredUsersId = $this->conn->fetchFirstColumn('SELECT UserID FROM Usr WHERE CreationDateTime ' . $this->getSqlDateFIlter());
        empty($registeredUsersId) ? $registeredUsersId = [0] : null;

        $result = [
            'title' . explode('::fetch', __METHOD__)[1] => '*Business stats for ' . $this->getDateRangeName() . '*',

            'countUserRegistered' => [
                'caption' => 'User registered',
                'value' => sprintf('%s',
                    $this->numberFormat($countRegisteredUsers = (int) $this->conn->executeQuery('SELECT COUNT(*) FROM Usr WHERE CreationDateTime ' . $this->getSqlDateFIlter())->fetchColumn())
                ),
            ],
        ];
        $countRefererRows = $this->conn->fetchAllAssociative('
            SELECT
                    Referer, COUNT(*) AS _CountReferer
            FROM Usr
            WHERE
                    CreationDateTime ' . $this->getSqlDateFIlter() . '
            GROUP BY Referer
            ORDER BY _CountReferer DESC
            LIMIT 5
        ');
        $countReferer = [];

        foreach ($countRefererRows as $item) {
            $item['Referer'] = empty($item['Referer']) ? '(direct)' : trim($item['Referer'], '/');
            $pad = $item['_CountReferer'] > 9 ? 2 : 3;
            $countReferer[] = '>_ ' . str_pad($item['_CountReferer'], $pad, ' ', STR_PAD_LEFT) . ' - ' . $item['Referer'] . ' _';
        }
        $result[] = ['value' => implode("\n", $countReferer)];

        $result = array_merge($result, $this->getAuthorizedDevices($registeredUsersId));

        $result[] = [
            'caption' => 'Total users deleted',
            'value' => sprintf(
                '%s',
                $this->numberFormat($countDeletedUsers = (int) $this->conn->executeQuery('SELECT COUNT(*) FROM UsrDeleted WHERE DeletionDate ' . $this->getSqlDateFIlter())->fetchColumn())
            ),
        ];

        $result[] = [
            'caption' => 'Net new users',
            'value' => sprintf(
                '%s (total: %s)',
                $this->numberFormat($countRegisteredUsers - $countDeletedUsers),
                $this->numberFormat($totalUsers)
            ),
        ];

        $result = array_merge($result, $this->getNewAccounts($registeredUsersId));

        if (!empty($this->extendMessages['mailboxes'])) {
            $sumMailboxes = array_sum($this->extendMessages['mailboxes']);
            $percentMailboxPerUsers = floor(count($this->extendMessages['mailboxes']) * 100 / count($registeredUsersId));
            $result['mailboxes'] = [
                'caption' => 'Total new mailboxes added',
                'value' => sprintf('%s (%d%% of new users added at least 1 mailbox)', $this->numberFormat($sumMailboxes), $percentMailboxPerUsers),
            ];
        } else {
            $result['mailboxes'] = [
                'caption' => 'Total new mailboxes added',
                'value' => '0',
            ];
        }

        $result[] = ['caption' => 'OneCard orders', 'value' => (int) $this->conn->executeQuery('SELECT COUNT(*) FROM OneCard WHERE OrderDate ' . $this->getSqlDateFIlter())->fetchColumn()];
        $revenue = $this->getTotalRevenue();

        [$totalNewSubscriptions, $subscriptionsWithCoupon] = $this->getNewSubscriptions();
        $totalSubscriptions = $this->conn->executeQuery("select count(*) from Usr where Subscription is not null")->fetchColumn();

        $result[] = 'New AwardWallet Plus subscriptions: ' . $totalNewSubscriptions . ' (total: ' . $this->numberFormat($totalSubscriptions) . ' / ' . round($totalSubscriptions / $totalUsers * 100, 2) . '%), '
            . $this->numberFormat($subscriptionsWithCoupon) . ' subscription' . ($subscriptionsWithCoupon > 1 || $subscriptionsWithCoupon == 0 ? 's' : '') . ' from applying coupons';
        $result[] = ['caption' => 'Total AwardWallet Plus Income', 'value' => sprintf('$%s (revenue: $%s)', $this->numberFormat($revenue['income']), $this->numberFormat($revenue['price']))];
        $result[] = ['caption' => 'New AwardBooking requests received', 'value' => (int) $this->conn->executeQuery('SELECT COUNT(*) FROM AbRequest WHERE CreateDate ' . $this->getSqlDateFIlter())->fetchColumn()];
        $result[] = ['caption' => 'Award Booking requests closed', 'value' => $this->getBookingRequestsClosed()];

        $bwCreditGived = (int) $this->conn->executeQuery('SELECT SUM(ci.Cnt) FROM Cart c, CartItem ci WHERE c.CartID = ci.CartID AND ci.TypeID = ' . BalanceWatchCredit::TYPE . ' AND ci.Price <= 0 AND PayDate ' . $this->getSqlDateFIlter())->fetchColumn();
        $bwCreditPurchased = (int) $this->conn->executeQuery('SELECT SUM(ci.Cnt) FROM Cart c, CartItem ci WHERE c.CartID = ci.CartID AND ci.TypeID = ' . BalanceWatchCredit::TYPE . ' AND ci.Price > 0 AND PayDate ' . $this->getSqlDateFIlter())->fetchColumn();
        $result[] = ['caption' => 'Balance Watch credits purchased', 'value' => $this->numberFormat($bwCreditPurchased)];
        $result[] = ['caption' => 'Balance Watch credits given away with Plus membership', 'value' => $this->numberFormat($bwCreditGived)];
        $result[] = ['caption' => 'Balance Watch credits used', 'value' => $this->numberFormat((int) $this->conn->executeQuery('SELECT COUNT(*) FROM BalanceWatch WHERE CreationDate ' . $this->getSqlDateFIlter())->fetchColumn())];

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function fetchSupport(): array
    {
        $result = [];
        $result['title' . explode('::fetch', __METHOD__)[1]] = '*Support stats for ' . $this->getDateRangeName() . '*';
        $result[] = ['caption' => 'New support requests received', 'value' => (int) $this->conn->executeQuery('SELECT COUNT(*) FROM ContactUs WHERE DateSubmitted ' . $this->getSqlDateFIlter())->fetchColumn()];

        return $result;
    }

    public function fetchDev(): array
    {
        $result = [];
        $result['title' . explode('::fetch', __METHOD__)[1]] = '*Dev stats for ' . $this->getDateRangeName() . '*';

        if (!empty($gitlog = $this->summaryGitlog())) {
            $result['totalGit'] = ['caption' => $this->formatDigit(sprintf('Total: %s lines inserted, %s deleted, %s commits, %s files', $gitlog['total']['inserted'], $gitlog['total']['deleted'], $gitlog['total']['commits'], $gitlog['total']['files']))];

            foreach ($gitlog['authors'] as $authorKey => $author) {
                $details = [];
                $details[] = (array_key_exists('inserted', $author) ? $this->numberFormat($author['inserted']) : '0') . ' lines inserted';

                foreach (['deleted', 'commits', 'files'] as $valueKey) {
                    $details[] = (array_key_exists($valueKey, $author) ? $author[$valueKey] : '0') . ' ' . $valueKey;
                }

                $result[] = [
                    'caption' => $author['author'],
                    'value' => implode(', ', $details),
                ];
            }
        } else {
            $result[] = ['caption' => '_git log is empty_'];
        }

        return $result;
    }

    public function fetchAffiliateQsTransaction(?\DateTime $setDate = null): array
    {
        $result = [
            'text' => '__',
            'blocks' => [],
        ];

        $isPassDate = null !== $setDate;
        $setDay = $setDate ?? (1 === (int) date('j') ? new \DateTime('-1 day') : null);

        $qsAwStat = $this->qsTransactionData->fetchByType(QsTransactionData::QS_TYPE_AW, $setDay);
        $qsAt101Stat = $this->qsTransactionData->fetchByType(QsTransactionData::QS_TYPE_AT101, $setDay);

        $dateMonth = new \DateTime($qsAwStat['interval']['month']['begin']);
        $sumEarnings = array_sum([$qsAwStat['totals']['month']['sumApprovalEarnings'], $qsAt101Stat['totals']['month']['sumApprovalEarnings']]);
        $sumClicks = array_sum([$qsAwStat['totals']['month']['sumClicks'], $qsAt101Stat['totals']['month']['sumClicks']]);

        if (!$isPassDate) {
            $awStatLastDay = (clone $qsAwStat['lastDay'])->sub(new \DateInterval('P1D'));
            $at101StatLastDay = (clone $qsAt101Stat['lastDay'])->sub(new \DateInterval('P1D'));
        } else {
            $awStatLastDay = $qsAwStat['lastDay'];
            $at101StatLastDay = $qsAt101Stat['lastDay'];
        }

        $formatter = new \NumberFormatter('en', \NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $result['blocks'] = [
            [
                'type' => 'section',
                'text' => [
                    'text' => $dateMonth->format('F Y') . ', Affiliate *Revenue* as of ' . $awStatLastDay->format('F d, Y'),
                    'type' => 'mrkdwn',
                ],
                'fields' => [
                    [
                        'type' => 'plain_text',
                        'text' => 'AwardWallet',
                    ],
                    [
                        'type' => 'plain_text',
                        'text' => '$' . $formatter->format($qsAwStat['totals']['month']['sumApprovalEarnings']),
                    ],
                    [
                        'type' => 'plain_text',
                        'text' => 'AT101',
                    ],
                    [
                        'type' => 'plain_text',
                        'text' => '$' . $formatter->format($qsAt101Stat['totals']['month']['sumApprovalEarnings']),
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => '*Total:*',
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => '*$' . $formatter->format($sumEarnings) . '*',
                    ],
                ],
            ],
            [
                'type' => 'divider',
            ],
            [
                'type' => 'section',
                'text' => [
                    'text' => $dateMonth->format('F Y') . ', Affiliate *Clicks* as of ' . $at101StatLastDay->format('F d, Y'),
                    'type' => 'mrkdwn',
                ],
                'fields' => [
                    [
                        'type' => 'plain_text',
                        'text' => 'AwardWallet',
                    ],
                    [
                        'type' => 'plain_text',
                        'text' => $this->numberFormat($qsAwStat['totals']['month']['sumClicks']),
                    ],
                    [
                        'type' => 'plain_text',
                        'text' => 'AT101',
                    ],
                    [
                        'type' => 'plain_text',
                        'text' => $this->numberFormat($qsAt101Stat['totals']['month']['sumClicks']),
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => '*Total:*',
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => '*' . $this->numberFormat($sumClicks) . '*',
                    ],
                ],
            ],
        ];

        return $result;
    }

    /**
     * @return string
     */
    public function getDateRangeName()
    {
        if ($this->dateAfter->format("H:i:s") == "00:00:00"
            && $this->dateBefore->format("H:i:s") == "23:59:59"
            && $this->dateAfter->format("Ymd") == $this->dateBefore->format("Ymd")
        ) {
            // one day
            return $this->dateAfter->format('l F j, Y');
        } else { // custom range
            return $this->dateAfter->format("Y-m-d H:i:s") . " - " . $this->dateBefore->format("Y-m-d H:i:s");
        }
    }

    /**
     * Gather all statistics on git.
     */
    public function summaryGitlog(): array
    {
        $sum = [
            'total' => array_combine(self::DETAIL_FIELDS, array_fill(0, count(self::DETAIL_FIELDS), 0)),
            'authors' => [],
        ];

        $allAuthors = [];

        foreach ($this->gitModules as $moduleName => $moduleFolder) {
            $allAuthors = array_merge($allAuthors, $this->fetchAuthors($moduleFolder));
            $gitlog = $this->gitLog($moduleFolder);

            if (!empty($gitlog)) {
                foreach ($gitlog['authors'] as $authorKey => $author) {
                    foreach ($sum['total'] as $key => $val) {
                        $sum['total'][$key] += $gitlog['authors'][$authorKey][$key] ?? 0;
                    }

                    array_key_exists($authorKey, $sum['authors']) ?: $sum['authors'][$authorKey] = [
                        'author' => $author['author'],
                        'email' => $author['email'],
                    ];

                    for ($i = -1, $iCount = count(self::DETAIL_FIELDS); ++$i < $iCount;) {
                        array_key_exists(self::DETAIL_FIELDS[$i], $sum['authors'][$authorKey]) ?: $sum['authors'][$authorKey][self::DETAIL_FIELDS[$i]] = 0;
                        !isset($author[self::DETAIL_FIELDS[$i]]) ?: $sum['authors'][$authorKey][self::DETAIL_FIELDS[$i]] += $author[self::DETAIL_FIELDS[$i]];
                    }
                }
            }
        }

        foreach ($allAuthors as $author => $data) {
            unset($allAuthors[$author]['commits']);
            $allAuthors[$author]['inserted'] = 0;
            $allAuthors[$author]['commits'] = 0;
        }

        $sum['authors'] = array_merge($allAuthors, $sum['authors']);
        $sum['authors'] = $this->arrayMultiSortBy($sum['authors'],
            'inserted', SORT_DESC,
            'deleted', SORT_DESC,
            'author', SORT_ASC);

        return $sum;
    }

    /**
     * Gathering statistics on the repository.
     *
     * @param string $moduleFolder
     * @throws ProcessFailedException|\RuntimeException
     */
    public function gitLog($moduleFolder = ''): array
    {
        if (empty($moduleFolder) || !file_exists($moduleFolder)) {
            throw new \OutOfBoundsException('Module folder "' . $moduleFolder . '" cannot be detected');
        }

        $result = [
            'total' => array_combine(self::DETAIL_FIELDS, array_fill(0, count(self::DETAIL_FIELDS), 0)),
        ];
        $gitlog = $this->fetchAuthors($moduleFolder, true);

        foreach ($result['total'] as $key => $val) {
            $result['total'][$key] = array_sum(array_column($gitlog, $key));
        }

        $arg = [
            '--all' => '',
            '--shortstat' => '',
            '--author' => '"%s"',
            '--after' => '"' . $this->dateAfter->format('Y-m-d H:i:s') . '"',
            '--before' => '"' . $this->dateBefore->format('Y-m-d H:i:s') . '"',

            '| grep -E "fil(e|es) changed"' => '',
            "| awk '{files+=$1; inserted+=$4; deleted+=$6} END {print files, inserted, deleted}'" => '',
        ];
        $args = '';

        foreach ($arg as $key => $value) {
            $args .= empty($value) ? $key . ' ' : $key . '=' . $value . ' ';
        }

        foreach ($gitlog as $key => $author) {
            $process = new Process('git log ' . sprintf($args, $author['author']), $moduleFolder);
            $process->setTimeout(180);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            $data = explode(' ', trim($output));

            for ($i = -1, $iCount = count($data); ++$i < $iCount;) {
                if (!empty($data[$i])) {
                    $gitlog[$key][self::DETAIL_FIELDS[1 + $i]] = $data[$i];
                }
            }
        }
        $result['authors'] = $gitlog;

        return $result;
    }

    /**
     * A list of all the authors who pushed to the git.
     *
     * @param string  $moduleFolder
     * @param bool $filter
     * @throws ProcessFailedException
     */
    public function fetchAuthors($moduleFolder = '', bool $byDate = false, $filter = true): array
    {
        $gitlog = [];
        $arg = ['--all' => ''];

        if ($byDate) {
            $arg['--after'] = '"' . $this->dateAfter->format('Y-m-d H:i:s') . '"';
            $arg['--before'] = '"' . $this->dateBefore->format('Y-m-d H:i:s') . '"';
        }
        $arg['--format'] = '"%aN|%aE"';
        $arg["| awk '{arr[$0]++} END{for (i in arr){print arr[i], i;}}'"] = '';

        $args = '';

        foreach ($arg as $key => $value) {
            $args .= empty($value) ? $key . ' ' : $key . '=' . $value . ' ';
        }

        $process = new Process('git log ' . trim($args), $moduleFolder);
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());
        $output = explode("\n", trim($output));

        // pass an array for collecting email with the @awardwallet without internal id
        for ($i = -1, $iCount = count($output); ++$i < $iCount;) {
            $row = trim($output[$i]);

            if (empty($row)) {
                continue;
            }
            $tmp = explode(' ', $row, 2);
            $author = explode('|', $tmp[1]);
            $id = strtolower(explode('@', $author[1])[0]);

            if (!isset($gitlog[$id]) && false !== stripos($author[1], '@awardwallet')) {
                $gitlog[$id] = [
                    '_preInit' => true,
                    'author' => $author[0],
                    'email' => $author[1],
                    'commits' => $tmp[0],
                ];
            }
        }

        for ($i = -1, $iCount = count($output); ++$i < $iCount;) {
            $row = trim($output[$i]);

            if (empty($row)) {
                continue;
            }
            $tmp = explode(' ', $row, 2);
            $author = explode('|', $tmp[1]);
            $id = strtolower(explode('@', $author[1])[0]);

            if (array_key_exists($id, $gitlog)) {
                if (isset($gitlog[$id]['_preInit'])) {
                    unset($gitlog[$id]['_preInit']);
                } else {
                    $gitlog[$id]['commits'] += $gitlog[$id]['commits'];
                }
            } else {
                $gitlog[$id] = [
                    'author' => $author[0],
                    'email' => $author[1],
                    'commits' => $tmp[0],
                ];
            }
        }

        $oneman = [
            'aanikin' => ['id' => ['anikinaleksey', 'anikin']],
            'aviktorov' => ['email' => ['demphest@gmail.com']],
            'akolomiytsev' => ['id' => ['aerond']],
            'stsvetkov' => ['id' => ['stvetkov', 'tsvetkov']],
            'eshumilov' => ['id' => ['evgeniy.shumilov']],
            'ylakatosh' => ['id' => ['yura.lakatosh']],
            'vladimir' => ['id' => ['vsilantyev']],
            'vpetuhov' => ['email' => ['vasily.norman@gmail.com']],
            'veresch' => ['id' => ['alexi']],
            'rshakirov' => ['email' => ['rhakirov@awardwallet.com', 'roman@awdevelopers-MacBook-Pro.local']],
            'iormark' => ['email' => ['iormark@ya.ru']],
            'aneklyudov' => ['id' => ['melnisc']],
            'apuzakov' => ['email' => ['puzakov59@gmail.com']],
            'pkovalev' => ['email' => ['45939355+KovalevPW@users.noreply.github.com']],
            'ksologub' => ['id' => ['sologub.kirill']],
            'vkurdin' => ['id' => ['vasiliy.kurdin']],
            'nabramov' => ['id' => ['k4zuki']],
            'kfamilion51' => ['id' => ['Linsor']],
            'mmishukov' => ['id' => ['109067893+mikhail-mishchukov']],
            'zstartseva' => ['id' => ['146829271+zstartseva']],
        ];

        foreach ($oneman as $owner => $extend) {
            if (array_key_exists('id', $extend)) {
                for ($i = -1, $iCount = count($extend['id']); ++$i < $iCount;) {
                    if (isset($gitlog[$extend['id'][$i]])) {
                        array_key_exists($owner, $gitlog) ?: $gitlog[$owner] = $gitlog[$extend['id'][$i]];
                        $gitlog[$owner]['commits'] += $gitlog[$extend['id'][$i]]['commits'];
                        $gitlog[$owner]['_author'][] = $gitlog[$extend['id'][$i]]['author'];
                        $gitlog[$owner]['_email'][] = $gitlog[$extend['id'][$i]]['email'];
                        unset($gitlog[$extend['id'][$i]]);
                    }
                }
            }

            if (array_key_exists('email', $extend)) {
                for ($i = -1, $iCount = count($extend['email']); ++$i < $iCount;) {
                    foreach ($gitlog as $gitowner => $gitauthor) {
                        if ($gitauthor['email'] === $extend['email'][$i]) {
                            array_key_exists($gitowner, $gitlog) ?: $gitlog[$owner] = $gitauthor;
                            $gitlog[$owner]['commits'] += $gitauthor['commits'];
                            $gitlog[$owner]['_author'][] = $gitauthor['author'];
                            $gitlog[$owner]['_email'][] = $gitauthor['email'];
                            unset($gitlog[$gitowner]);
                        }
                    }
                }
            }
        }

        if (is_bool($filter) && true === $filter) {
            return $this->filterAuthors($gitlog);
        }

        return $gitlog;
    }

    public function combineData(array $data): array
    {
        return $this->message = array_merge($this->message, array_map(function ($item) {
            if (is_string($item)) {
                return $item;
            }

            if (1 === count($item)) {
                return $item[key($item)];
            }

            return $item['caption'] . (array_key_exists('value', $item) ? ': ' . trim($this->formatDigit(' ' . $item['value'] . ' ')) : '');
        }, $data), ['']);
    }

    /**
     * @param string|array $message
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setMessage($message): self
    {
        if (is_array($message)) {
            $this->message = $message;
        } elseif (is_string($message)) {
            $this->message = [$message];
        } else {
            throw new \InvalidArgumentException('The message must be an array or string');
        }

        return $this;
    }

    public function getMessage(): array
    {
        return $this->message;
    }

    public function getStats(): string
    {
        return implode("\n", $this->message);
    }

    public function formatDigit(string $str): string
    {
        return preg_replace_callback('/ \d+? /', function ($item) {
            return ' ' . $this->numberFormat(trim($item[0])) . ' ';
        }, $str);
    }

    /**
     * @throws \Exception
     */
    public function getTotalRevenue(): array
    {
        require_once $GLOBALS['sPath'] . '/manager/reports/common.php';

        $total = [
            'price' => 0,
            'fee' => 0,
            'income' => 0,
            'recurring' => 0,
        ];

        $carts = $this->conn->executeQuery(getPaymentsSql($this->dateAfter->getTimestamp(), $this->dateBefore->getTimestamp() + 1))->fetchAll();

        foreach ($carts as &$cart) {
            calcProfit($cart['PaymentType'], $cart['Price'], $cart['Fee'], $cart['Income']);
            $total['price'] += $cart['Price'];
            $total['fee'] += $cart['Fee'];
            $total['income'] += $cart['Income'];

            if ('Recurring' === $cart['Recurring']) {
                $total['recurring'] += $cart['Income'];
            }
        }

        $total = array_map(function ($item) {
            return round($item);
        }, $total);

        return $total;
    }

    public function setDay(\DateTime $dateTime)
    {
        $this->dateAfter = $dateTime;
        $this->dateAfter->modify("midnight");
        $this->setDateBefore();
    }

    public function getDateFilter(): array
    {
        return [$this->dateAfter, $this->dateBefore];
    }

    private function getSqlDateFilter()
    {
        return ' BETWEEN ' . $this->conn->quote($this->dateAfter->format('Y-m-d H:i:s')) . ' AND ' . $this->conn->quote($this->dateBefore->format('Y-m-d H:i:s'));
    }

    /**
     * @return int[]
     */
    private function getNewSubscriptions()
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();
        $q = $manager->createQuery("select c from AwardWallet\MainBundle\Entity\Cart c 
        where c.paydate >= :startDate and c.paydate <= :endDate and c.source = " . Cart::SOURCE_USER);
        $carts = $q->execute(["startDate" => $this->dateAfter, "endDate" => $this->dateBefore]);
        $totalSubscriptions = 0;
        $subscriptionsWithCoupon = 0;

        foreach ($carts as $cart) {
            /** @var Cart $cart */
            if ($cart->isAwPlusSubscription()) {
                $totalSubscriptions++;
            }

            if ($cart->isAwPlusSubscription() && $cart->getCoupon() && $cart->getCoupon()->getFirsttimeonly()) {
                $subscriptionsWithCoupon++;
            }
        }

        return [$totalSubscriptions, $subscriptionsWithCoupon];
    }

    private function getBookingRequestsClosed()
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();
        $q = $manager->createQuery("select m from AwardWallet\MainBundle\Entity\AbMessage m 
        where m.CreateDate >= :startDate and m.CreateDate <= :endDate
        and m.Type = " . AbMessage::TYPE_STATUS_REQUEST);
        $messages = $q->execute(["startDate" => $this->dateAfter, "endDate" => $this->dateBefore]);
        $requests = [];

        foreach ($messages as $message) {
            /** @var AbMessage $message */
            if ($message->getMetadata()->getStatus() == AbRequest::BOOKING_STATUS_BOOKED) {
                $requests[] = $message->getRequest()->getAbRequestID();
            }
        }

        return count(array_unique($requests));
    }

    private function arrayMultiSortBy()
    {
        $args = func_get_args();
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];

                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field] ?? 0;
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    private function filterAuthors($authors = [])
    {
        $old = ['myanovskaya', 'workingmarysmail', 'vpetuhov', 'stsvetkov', 'dvinokurov', 'asedochenko', 'sreutov', 'dborisenko', 'ashilov', 'arazin', 'imoskalenko',
            'aivanovskiy', 'estarkov', 'vmalygin', 'rvaleev', 'tsvetkov', 'stvetkov', 'dryabov', 'aloginov', 'nteplyakov', 'nkondratenko', 'piohin', 'alykov',
            'mshaimardanov', 'ialabuzheva', 'yborisenko', 'ykorobeinikov', 'gplehanov', 'ybelov', '*roman.shmih', 'roman.shmih', 'contact', 'skalinin', 'rkiriyak',
            'irina.zvarych', 'andrey.golovko', 'vladimir.tyrin', 'doctor-86', 'dbigday', 'akazakova', 'pkovalev', 'nspasennikova', 'aneklyudov', 'eshumilov',
            'truecat17', 'arepukhov', 'akolomiytsev', 'it-exxpert', 'rshakirov', 'mtomilov', 'apuzakov', 'ksologub', 'ylakatosh', 'skuklin', 'bsaranin', '79647703+octoberguy',
            'dneudakhin', '117341553+dneudakhin', 'piohin', '79647703+octoberguy', 'moparin', 'suddsumm', 'medombas',
        ];
        $sys = ['support', 'sysadmin', 'awardwallet', 'vagrant', 'jenkins', 'awdeveloper', 'root', 'ubuntu', '=', 'example', 'snyk-bot'];

        $filterId = array_merge($old, $sys);

        for ($i = -1, $iCount = count($filterId); ++$i < $iCount;) {
            unset($authors[$filterId[$i]]);
        }

        return $authors;
    }

    private function numberFormat($number, $decimal = 0): string
    {
        return number_format($number, $decimal, '.', ',');
    }

    private function setDateBefore()
    {
        $this->dateBefore = clone $this->dateAfter;
        $this->dateBefore->modify("+1 day")->modify("-1 second");
        $this->logger->info("date range is: " . $this->dateAfter->format("Y-m-d H:i:s") . " to " . $this->dateBefore->format("Y-m-d H:i:s"));
    }

    private function getAuthorizedDevices($registeredUsersId): array
    {
        $result = [];

        $androidLoggedCount = $this->getLoggedUsers($registeredUsersId, 1);
        $iosLoggedCount = $this->getLoggedUsers($registeredUsersId, 2);
        $desktopLoggedCount = $this->getLoggedUsers($registeredUsersId, 3);

        $mobileBrowserCount = $this->getLoggedUsers($registeredUsersId, 4);

        $result['loggedIos'] = [
            'caption' => 'New users (only who registered today) logged into *iOS app*',
            'value' => sprintf('%s', $this->numberFormat($iosLoggedCount)),
        ];
        $result['loggedAndroid'] = [
            'caption' => 'New users (only who registered today) logged into *Android app*',
            'value' => sprintf('%s', $this->numberFormat($androidLoggedCount)),
        ];
        $result['loggedOtherMobile'] = [
            'caption' => 'New users (only who registered today) logged into *Mobile Web Interface*',
            'value' => sprintf('%s', $this->numberFormat(max($mobileBrowserCount, 0))),
        ];
        $result['loggedDesktop'] = [
            'caption' => 'New users (only who registered today) who *did not use mobile*',
            'value' => sprintf('%s', $this->numberFormat($desktopLoggedCount)),
        ];

        return $result;
    }

    private function getLoggedUsers(array $usersId, int $deviceType): int
    {
        switch ($deviceType) {
            case 1: // mobile android
                $conditions = "Browser LIKE 'Mobile App' AND Platform LIKE 'Android' AND IsMobile = 1";

                break;

            case 2: // mobile ios
                $conditions = "Browser LIKE 'Mobile App' AND Platform LIKE 'iOS' AND IsMobile = 1";

                break;

            case 3: // desktop
                $conditions = 'IsDesktop = 1 AND AuthType = 2';

                break;

            case 4: // other mobile or mobile (browser
                $conditions = "AuthType = 1 AND Browser NOT LIKE 'Mobile App'";

                break;

            default:
                throw new \Exception('Unknown device type');
        }

        $usersConditions = 'UserID IN (' . implode(',', $usersId) . ')';

        $authUsers = $this->conn->fetchAllAssociative('
            SELECT DISTINCT UserID
            FROM `UserAuthStat`
            WHERE
                    ' . $usersConditions . '
                AND CreateDate ' . $this->getSqlDateFilter() . '
                AND ' . $conditions . '
        ');

        return count($authUsers);
    }

    private function getNewAccounts($registeredUsersId): array
    {
        $result = [];
        $registerdUsers = 'UserID IN (' . implode(',', $registeredUsersId) . ')';

        $countAccounts = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM Account WHERE ' . $registerdUsers . ' AND CreationDate ' . $this->getSqlDateFIlter());
        $countCoupons = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ProviderCoupon WHERE ' . $registerdUsers . ' AND CreationDate ' . $this->getSqlDateFIlter());
        $sum = $countAccounts + $countCoupons;

        $result['newAccounts'] = [
            'caption' => 'Total new loyalty accounts added',
            'value' => sprintf(
                '%s (%s accounts per User)',
                $this->numberFormat($sum),
                $this->numberFormat(floor($sum / count($registeredUsersId)))
            ),
        ];

        return $result;
    }
}
