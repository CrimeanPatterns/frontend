<?php

namespace AwardWallet\MainBundle\Service\User\Async;

use AwardWallet\MainBundle\Entity\CartItem\At201Items;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\PlusItems;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\Quinstreet\UpdateQsTransactionQmpCommand;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Symfony\Component\Routing\RouterInterface;

require_once __DIR__ . '/../../../../../../web/manager/reports/common.php';

class UserStatExecutor implements ExecutorInterface
{
    private SocksClient $sockClicent;
    private Connection $connection;
    private LocalizeService $localizeService;
    private Logger $logger;
    private EmailScannerApi $emailScannerApi;
    private \Memcached $memcached;
    private RouterInterface $router;

    private string $sqlFilterUserIds;
    private array $usersCount = [];
    private array $debug = [];

    public function __construct(
        SocksClient $sockClicent,
        Connection $connection,
        LocalizeService $localizeService,
        Logger $logger,
        EmailScannerApi $emailScannerApi,
        \Memcached $memcached,
        RouterInterface $router
    ) {
        $this->sockClicent = $sockClicent;
        $this->connection = $connection;
        $this->localizeService = $localizeService;
        $this->logger = $logger;
        $this->emailScannerApi = $emailScannerApi;
        $this->memcached = $memcached;
        $this->router = $router;
    }

    /**
     * @param UserStatTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $this->logger->info('UserStatExecutor: execute');

        $conditions = $task->getConditions();

        if (!empty($conditions)) {
            $data = $this->getData($conditions);

            if (isset($data['report'])) {
                $this->sockClicent->publish(
                    $task->getResponseChannel(),
                    [
                        'type' => 'report',
                        'link' => $this->router->generate('aw_manager_user_stat_report', ['key' => $data['key']]),
                    ]
                );
            } else {
                if (!isset($data['data1'])) {
                    $this->sockClicent->publish(
                        $task->getResponseChannel(),
                        ['type' => 'table1', 'html' => $data['html']]
                    );

                    return new Response();
                }
                $this->sockClicent->publish(
                    $task->getResponseChannel(),
                    [
                        'type' => 'table1',
                        'html' => $this->drawTable($data['data1']),
                    ]
                );
                $this->sockClicent->publish(
                    $task->getResponseChannel(),
                    [
                        'type' => 'table2',
                        'html' => $this->drawTable($data['data2']),
                    ]
                );
                /*
                $table1Data = $this->formatData($data['data1']);
                foreach ($table1Data as $row) {
                    $this->sockClicent->publish(
                        $task->getResponseChannel(),
                        [
                            'type' => 'row1',
                            'row' => $row,
                        ]
                    );
                }
                */
            }
        }

        return new Response();
    }

    private function getData(array $conditions): array
    {
        $creationDate = $conditions['creationDate'];
        $duration = $conditions['duration'];
        $durationDate = $conditions['durationDate'];
        $isDuration = $conditions['isDuration'];
        $isMobileApp = $conditions['isMobileApp'];
        $isPurchasedAwPlus = $conditions['isPurchasedAwPlus'];
        $cusers = $conditions['users'];
        $filters = $conditions['filters'];
        $baseLead = $conditions['baseLead'];
        $typeSubmit = $conditions['typeSubmit'];
        $isDebug = $conditions['isDebug'] ?? false;

        $result = ['durationDate' => null];
        $html = '';

        // --

        if (empty($creationDate['min'])) {
            $creationDate['min'] = '2000-01-01 00:00:00';
        }

        if (empty($creationDate['max'])) {
            $creationDate['max'] = date('Y-m-d H:i:s');
        }

        $where = ['u.CreationDateTime BETWEEN ' . $this->connection->quote($creationDate['min']) . ' AND ' . $this->connection->quote($creationDate['max'])];
        $having = [];

        $minMaxFilters = $this->getMinMaxFilters($filters);
        $minMaxFilters = empty($minMaxFilters) ? '' : ' AND ' . implode(' AND ', $minMaxFilters);

        // ----

        // Достаем всех пользователей за указанный период
        $userCountByDateRange = $this->connection->fetchOne('
            SELECT COUNT(*)
            FROM Usr u
            WHERE ' . implode(' AND ', $where) . '
        ');
        $usUserCountByDateRange = $this->connection->fetchOne('
            SELECT COUNT(*)
            FROM Usr u
            WHERE IsUs = 1 AND ' . implode(' AND ', $where) . '
        ');

        // Если есть фильтр джоиним нужную таблицу
        $joinTravelPlans = $filters['TPlans']['min'] > 0 || $filters['TPlans']['max'] > 0
            ? 'JOIN Plan p ON (u.UserID = p.UserID)' : '';

        // Те кто установил мобильное приложение
        $joinMobileDevice = $isMobileApp
            ? 'JOIN MobileDevice md ON (
                        md.UserID = u.UserID
                    AND md.DeviceType IN (' . implode(',', [MobileDevice::TYPE_ANDROID, MobileDevice::TYPE_IOS]) . ')
               )' : '';

        // Те кто совершал оплату AW+
        $joinPurchaseAwPlus = $isPurchasedAwPlus
            ? 'JOIN Cart c ON (c.UserID = u.UserID)
               JOIN CartItem ci ON (
                        c.CartID = ci.CartID
                    AND ci.TypeID IN (' . implode(',', PlusItems::getTypes()) . ' AND ci.Price > 0)
               )' : '';

        if ($isPurchasedAwPlus) {
            $having[] = 'SUM(ci.Price) > 0';
        }

        // Фильтр, если пользователь должен иметь аккаунты
        if ($filters['Programs']['min'] > 0 || $filters['Programs']['max'] > 0) {
            $where[] = 'u.Accounts > 0';
        }

        // Фильтр на наличие мейлбоксов
        if ($filters['Mailboxes']['min'] > 0 || $filters['Mailboxes']['max'] > 0) {
            $where[] = 'u.ValidMailboxesCount > 0';
        }

        // Кто переходил по ссылкам для открытия карт
        $joinQsClicks = $filters['CCClicks']['min'] > 0 || $filters['CCClicks']['max'] > 0
            ? 'JOIN QsTransaction qtClicks ON (u.UserID = qtClicks.UserID)' : '';

        // Кому одобрили заявку
        $joinQsApprovals = $filters['CCApprovals']['min'] > 0 || $filters['CCApprovals']['max'] > 0
            ? 'JOIN QsTransaction qtApprovals ON (u.UserID = qtApprovals.UserID AND qtApprovals.Approvals > 0)' : '';

        if (1 === $cusers) {
            $where[] = 'u.IsUs = 1';
        } elseif (2 === $cusers) {
            $where[] = 'u.IsUs = 0';
        }

        if (!empty($baseLead)) {
            $where[] = 'u.CameFrom IN (' . implode(',', $baseLead) . ') ';
        }

        $having = empty($having) ? 1 : implode(' AND ', $having);

        $this->sqlFilterUserIds = $sqlFilterUserIds = '
            SELECT u.UserID
            FROM Usr u
            ' . $joinMobileDevice . '
            ' . $joinPurchaseAwPlus . '
            ' . $joinTravelPlans . '
            ' . $joinQsClicks . '
            ' . $joinQsApprovals . '
            WHERE
                    ' . implode(' AND ', $where) . '
                    ' . $minMaxFilters . '
            GROUP BY u.UserID
            HAVING ' . $having . '
        ';

        $usersData = $this->connection->fetchAllKeyValue(str_replace(
            'SELECT u.UserID',
            'SELECT u.UserID, UNIX_TIMESTAMP(u.CreationDateTime) as _CreationDateTime',
            $sqlFilterUserIds
        ));

        $qsStat = $this->getQsStat($usersData, $creationDate, $duration);

        $totalAll = $this->getTotalPaymentsByDurationForAll($usersData, $creationDate, $duration);
        $totalPlusPays = $totalAll['plus'];
        $totalAtPays = $totalAll['at'];
        $totalOtherPays = $totalAll['other'];

        // $totalPlusPays = $this->getTotalPaymentsByDuration($usersData, $creationDate, $duration, 'plus');
        // $totalAtPays = $this->getTotalPaymentsByDuration($usersData, $creationDate, $duration, 'at');
        // $totalOtherPays = $this->getTotalPaymentsByDuration($usersData, $creationDate, $duration, 'other');

        $usersMobileCount = $this->getMobileUsersCount($usersData);

        $lpCount = $this->getLpCount($usersData, $creationDate, $duration);
        $tripCount = $this->getTripCount($usersData, $creationDate, $duration);
        $mailboxesCount = $this->getMailboxesCount($usersData, $creationDate, $duration);

        $totalRevenue = $qsStat['earnings'] + $totalPlusPays['price'] + $totalAtPays['price'] + $totalOtherPays['price'];

        $matchedUsersCount = $isDuration && !empty($minMaxFilters) ? count($this->usersCount) : count($usersData);
        $nonUsUsersCount = ($userCountByDateRange - $usUserCountByDateRange);

        if ('report_var2' === $typeSubmit) {
            return $this->getCsvReportVar2($usersData);
        }

        $data1 = [
            ['title' => 'Total Accounts in Date Range', 'value' => $userCountByDateRange],
            ['title' => 'US Accounts in Date Range', 'value' => $usUserCountByDateRange],
            ['title' => 'Non US Accounts in Date Range', 'value' => $nonUsUsersCount],
            ['title' => 'Registered via mobile (~not exactly)', 'value' => $usersMobileCount],
            ['title' => 'AW Accounts Matching All Filters', 'value' => $matchedUsersCount],

            ['type' => 'separate'],

            ['title' => 'Total LPs', 'value' => $lpCount],
            ['title' => 'Total Trips', 'value' => $tripCount],
            [
                'title' => 'Connected Mailboxes',
                'value' => $mailboxesCount,
            ],
            ['title' => 'CC Clicks', 'value' => $qsStat['clicks']],
            ['title' => 'CC Approvals', 'value' => $qsStat['approvals']],

            ['type' => 'separate'],
            ['type' => 'separate'],
            ['title' => 'Total CC Revenue', 'value' => $qsStat['earnings'], 'type' => 'currency'],

            ['type' => 'separate'],
            ['title' => 'Total AW Plus Payments', 'value' => $totalPlusPays['countPayments']],
            [
                'title' => 'Total AW Plus Revenue'
                    . '<br>&mdash;<sup>fees: '
                    . $this->localizeService->formatCurrency($totalPlusPays['fee'], 'USD')
                    . ', income: ' . $this->localizeService->formatCurrency($totalPlusPays['income'],
                        'USD') . '</sup>',
                'value' => $totalPlusPays['price'],
                'type' => 'currency',
            ],

            ['type' => 'separate'],
            ['title' => 'Total AT 201 Payments', 'value' => $totalAtPays['countPayments']],
            [
                'title' => 'Total AT 201 Revenue'
                    . '<br>&mdash;<sup>fees: '
                    . $this->localizeService->formatCurrency($totalAtPays['fee'], 'USD')
                    . ', income: ' . $this->localizeService->formatCurrency($totalAtPays['income'], 'USD') . '</sup>',
                'value' => $totalAtPays['price'],
                'type' => 'currency',
            ],

            ['type' => 'separate'],
            ['title' => 'Total Other Payments', 'value' => $totalOtherPays['countPayments']],
            [
                'title' => 'Total Other Revenue'
                    . '<br>&mdash;<sup>fees: ' . $this->localizeService->formatCurrency($totalOtherPays['fee'],
                        'USD') . ', income: ' . $this->localizeService->formatCurrency($totalOtherPays['income'],
                            'USD') . '</sup>',
                'value' => $totalOtherPays['price'],
                'type' => 'currency',
            ],

            ['type' => 'separate'],
            ['type' => 'separate'],
            [
                'title' => 'Total Revenue',
                'value' => $totalRevenue,
                'type' => 'currency',
            ],
        ];

        $data2 = [
            [
                'title' => 'Total Cohort Revenue / User ('
                    . $this->localizeService->formatNumber($totalRevenue, 2)
                    . '/' . $this->localizeService->formatNumber($matchedUsersCount)
                    . ')',
                'value' => $totalRevenue / $matchedUsersCount,
                'type' => 'currency',
            ],
            [
                'title' => 'AW Plus Earnings per user (' . $this->localizeService->formatNumber($totalPlusPays['price']) . '/' . $this->localizeService->formatNumber($matchedUsersCount) . ')',
                'value' => $totalPlusPays['price'] / $matchedUsersCount,
                'type' => 'currency',
            ],
        ];

        if ($qsStat['clicks'] > 0) {
            $data2 = array_merge($data2, [
                [
                    'title' => 'CC earnings per user',
                    'value' => $qsStat['earnings'] / $matchedUsersCount,
                    'type' => 'currency',
                ],
                ['title' => 'CC approval / User', 'value' => $qsStat['approvals'] / $matchedUsersCount],
                [
                    'title' => 'Earnings per CC Approval',
                    'value' => $qsStat['approvals'] > 0 ? $qsStat['earnings'] / $qsStat['approvals'] : 0,
                    'type' => 'currency',
                ],
            ]);
        }

        if ($isDebug) {
            $data2 = array_merge($data2, [
                [
                    'title' => 'test',
                    'value' => var_export($this->test($creationDate['min'], $creationDate['max']), true),
                    'type' => 'string',
                ],
                [
                    'title' => 'test2',
                    'value' => var_export($this->debug, true),
                    'type' => 'string',
                ],
            ]);
        }

        $table1 = $this->drawTable($data1);
        $table2 = $this->drawTable($data2);

        if ($qsStat['clicks'] <= 0) {
            $table2 .= '<hr>CC QsTransaction is empty';
        }

        $html .= '
        <div style="display: flex;">
            <div>' . $table1 . '</div>
            <div>' . $table2 . '</div>
        </div>
        ';

        return [
            'html' => $html,
            'data1' => $data1,
            'data2' => ($data2 ?? []),
        ] + $result;
    }

    private function getCsvReportVar2(
        array $usersData
    ): array {
        $cackeKey = sha1(time());

        $baseleads = $this->connection->fetchAllKeyValue('
            SELECT s.SiteAdID, s.Description
            FROM SiteAd s
        ');

        $out = [];

        $usersId = array_keys($usersData);
        $usersIdChunk = array_chunk($usersId, 15000);

        foreach ($usersIdChunk as $userIds) {
            $users = $this->connection->fetchAllAssociative('
                SELECT u.UserID, u.IsUs, u.Referer, u.CameFrom
                FROM Usr u
                WHERE u.UserID IN (' . implode(',', $userIds) . ')
            ');

            foreach ($users as $user) {
                $userId = $user['UserID'];

                $urlParams = parse_url($user['Referer']);
                $var2 = [];

                if (!empty($urlParams['query'])) {
                    parse_str(str_replace('var2=', 'var2[]=', $urlParams['query']), $query);
                    $var2 = UpdateQsTransactionQmpCommand::parseVar2($query['var2'][0] ?? '');
                    $var2b = UpdateQsTransactionQmpCommand::parseVar2($query['var2'][1] ?? '');
                }

                $blogPostId = $var2['BlogPostID'] ?? $var2b['BlogPostID'] ?? '';
                $mid = $var2['MID'] ?? $var2b['MID'] ?? '';
                $cid = $var2['CID'] ?? $var2b['CID'] ?? '';

                if ('undefined' === $mid) {
                    $mid = '';
                }

                if ('undefined' === $cid) {
                    $cid = '';
                }

                $out[] = [
                    'userId' => $userId,
                    'registered' => date('m/d/Y', $usersData[$userId]),
                    'isUs' => 1 === (int) $user['IsUs'] ? 'true' : 'false',
                    'referer' => $user['Referer'],
                    'baselead' => $baseleads[$user['CameFrom']] ?? '',
                    'var2_source' => $var2['Source'] ?? $var2['Exit'] ?? $var2b['Source'] ?? $var2b['Exit'] ?? '',
                    'var2_postId' => $blogPostId,
                    'var2_mid' => $mid,
                    'var2_cid' => $cid,
                    'postUrl' => 'https://awardwallet.com'
                        . (empty($blogPostId) ? '/' . ltrim($urlParams['path'] ?? '', '/') : '/blog/?p=' . $blogPostId),
                ];
            }
        }

        $this->memcached->set($cackeKey, $out, 60 * 60);

        return [
            'report' => true,
            'key' => $cackeKey,
        ];
    }

    private function getMinMaxFilters(array $minMax): array
    {
        $filters = [];

        if ($minMax['Programs']['min'] > 0) {
            $filters[] = ' u.Accounts >= ' . $minMax['Programs']['min'];
        }

        if ($minMax['Programs']['max'] > 0) {
            $filters[] = ' u.Accounts <= ' . $minMax['Programs']['max'];
        }

        if ($minMax['Mailboxes']['min'] > 0) {
            $filters[] = ' u.ValidMailboxesCount >= ' . $minMax['Mailboxes']['min'];
        }

        if ($minMax['Mailboxes']['max'] > 0) {
            $filters[] = ' u.ValidMailboxesCount <= ' . $minMax['Mailboxes']['max'];
        }

        return $filters;
    }

    private function getEndDate(int $startDate, array $datePeriod, int $duration): int
    {
        if ($duration) {
            $durationDate = new \DateTime('@' . ($startDate + ($duration * 86400)));
            $durationDate->setTime(23, 59, 59);

            return $durationDate->getTimestamp();
        }

        return strtotime($datePeriod['max']);
    }

    private function getLpCount($users, $datePeriod, $duration): int
    {
        if (!$duration) {
            return (int) $this->connection->fetchOne('
                SELECT
                    COUNT(a.AccountID) AS sumAccounts
                FROM Usr u
                JOIN Account a ON (a.UserID = u.UserID)
                WHERE
                        u.UserID IN (' . $this->sqlFilterUserIds . ')
                    AND UNIX_TIMESTAMP(a.CreationDate) <= ' . $this->getEndDate(0, $datePeriod, $duration) . ' 
            ');
        }

        $count = 0;

        foreach ($users as $userId => $creationDate) {
            $sql = '
                SELECT COUNT(*)
                FROM Account
                WHERE
                        UserID = ' . $userId . '
                    AND UNIX_TIMESTAMP(CreationDate) <= ' . $this->getEndDate($creationDate, $datePeriod, $duration) . '
            ';
            $resCount = (int) $this->connection->fetchOne($sql);

            if ($resCount) {
                $count += $resCount;
                $this->putUserCount($userId);
            }
        }

        return $count;
    }

    private function getTripCount($users, $datePeriod, $duration): int
    {
        if (!$duration) {
            return (int) $this->connection->fetchOne('
                SELECT COUNT(DISTINCT RecordLocator)
                FROM Trip
                WHERE
                        UserID IN (' . $this->sqlFilterUserIds . ')
                    AND UNIX_TIMESTAMP(CreateDate) <= ' . $this->getEndDate(0, $datePeriod, $duration) . '
            ');
        }

        $count = 0;

        foreach ($users as $userId => $creationDate) {
            $sql = '
                SELECT COUNT(*)
                FROM Trip
                WHERE
                        UserID = ' . $userId . '
                    AND UNIX_TIMESTAMP(CreateDate) <= ' . $this->getEndDate($creationDate, $datePeriod, $duration) . '
            ';
            $resCount = (int) $this->connection->fetchOne($sql);

            if ($resCount) {
                $count += $resCount;
                $this->putUserCount($userId);
            }
        }

        return $count;
    }

    private function getMailboxesCount($users, $datePeriod, $duration): int
    {
        if (!$duration) {
            // Нет ограничения на End Date т.к. пользователи уже были выбраны за необходимый период
            return (int) $this->connection->fetchOne('
                SELECT SUM(ValidMailboxesCount)
                FROM Usr
                WHERE UserID IN (' . $this->sqlFilterUserIds . ')
            ');
        }

        $count = 0;

        foreach ($users as $userId => $creationDate) {
            $mailboxes = $this->emailScannerApi->listMailboxes(['user_' . $userId], [Mailbox::STATE_LISTENING]);

            /** @var Mailbox $mailbox */
            foreach ($mailboxes as $mailbox) {
                $mailboxCreationDate = new \DateTime($mailbox->getCreationDate());

                if ($mailboxCreationDate->getTimestamp() <= $this->getEndDate($creationDate, $datePeriod, $duration)) {
                    $count++;
                    $this->putUserCount($userId);
                }
            }
        }

        return $count;
    }

    private function getQsStat($users, $datePeriod, $duration): array
    {
        $totals = ['clicks' => 0, 'approvals' => 0, 'earnings' => 0];

        foreach ($users as $userId => $creationDate) {
            $sql = '
                SELECT
                        Clicks, Approvals, Earnings
                FROM QsTransaction qt
                WHERE
                       qt.UserID = ' . $userId . '
                   AND UNIX_TIMESTAMP(qt.CreationDate) <= ' . $this->getEndDate($creationDate, $datePeriod, $duration) . '
            ';
            $rows = $this->connection->fetchAllAssociative($sql);

            foreach ($rows as $row) {
                $totals['clicks'] += (int) $row['Clicks'];
                $totals['approvals'] += (int) $row['Approvals'];

                if ((int) $row['Approvals'] > 0) {
                    $totals['earnings'] += (float) $row['Earnings'];
                }
            }

            if (!empty($rows)) {
                $this->putUserCount($userId);
            }
        }

        return $totals;
    }

    private function getTotalPaymentsByDurationForAll($users, $datePeriod, $duration): array
    {
        $totalKeys = ['price' => 0, 'fee' => 0, 'income' => 0, 'countPayments' => 0];
        $totalPays = [
            'plus' => $totalKeys,
            'at' => $totalKeys,
            'other' => $totalKeys,
        ];

        $plusTypes = array_merge(
            PlusItems::getTypes(),
            [AwPlusGift::TYPE, AwPlusRecurring::TYPE]
        );
        $atTypes = At201Items::getTypes();

        foreach ($users as $userId => $creationDate) {
            $endDate = $duration
                ? "and c.PayDate <= '" . date("Y-m-d H:i:s",
                    $this->getEndDate($creationDate, $datePeriod, $duration)) . "'"
                : '';

            $sql = "
                select
                    c.PaymentType, c.CartID,
                    sum(ci.Price * ci.Cnt * (100 - ci.Discount)/100) as Total,
                    max(case when ci.TypeID in (" . implode(',', $plusTypes) . ") then 1 else 0 end) as plusType,
                    max(case when ci.TypeID in (" . implode(',', $atTypes) . ") then 1 else 0 end) as at201Type
                from Usr u 
                join Cart c on c.UserID = u.UserID 
                join CartItem ci on ci .CartID = c.CartID 
                where
                        u.UserID = {$userId}
                    {$endDate}
                    and c.PayDate is not null
                    and ci.Price <> 0
                    and ci.ScheduledDate is null 
                group by c.UserID, c.CartID
                having Total > 0
            ";

            $payments = $this->connection->fetchAllAssociative($sql);

            foreach ($payments as $pay) {
                $this->debug[] = [
                    'CartID' => $pay['CartID'],
                    'Total' => $pay['Total'],
                    'UserID' => $userId,
                ];

                calcProfit($pay['PaymentType'], $pay['Total'], $pay['Fee'], $pay['Income']);

                $isPlus = (int) $pay['plusType'];
                $isAt201 = (int) $pay['at201Type'];

                $payKey = $isPlus
                    ? 'plus'
                    : ($isAt201 ? 'at' : 'other');

                $totalPays[$payKey]['price'] += $pay['Total'];
                $totalPays[$payKey]['fee'] += $pay['Fee'];
                $totalPays[$payKey]['income'] += $pay['Income'];

                if ($pay['Income'] > 0) {
                    ++$totalPays[$payKey]['countPayments'];
                }
            }

            if (!empty($payments)) {
                $this->putUserCount($userId);
            }
        }

        return $totalPays;
    }

    private function getTotalPaymentsByDuration($users, $datePeriod, $duration, $type): array
    {
        $totalPays = ['price' => 0, 'fee' => 0, 'income' => 0, 'countPayments' => 0];

        $plusTypes = array_merge(
            PlusItems::getTypes(),
            [AwPlusGift::TYPE, AwPlusRecurring::TYPE]
        );
        $atTypes = At201Items::getTypes();

        if ('plus' === $type) {
            $typesCondition = 'AND ci.TypeID IN (' . implode(',',
                array_merge($plusTypes, [Discount::TYPE])
            ) . ')';
        } elseif ('at' === $type) {
            $typesCondition = 'AND ci.TypeID IN (' . implode(',',
                array_merge($atTypes, [Discount::TYPE])
            ) . ')';
        } else {
            $typesCondition = 'AND ci.TypeID NOT IN (' . implode(',', array_merge($atTypes, $plusTypes)) . ')';
        }

        foreach ($users as $userId => $creationDate) {
            $endDate = $duration
                ? "and c.PayDate <= '" . date("Y-m-d H:i:s",
                    $this->getEndDate($creationDate, $datePeriod, $duration)) . "'"
                : '';

            $sql = "
                select
                    c.PaymentType,
                    sum(ci.Price * ci.Cnt * ((100-ci.Discount)/100)) as Price,
                    max(case when ci.UserData = " . CART_FLAG_RECURRING . " then 'Recurring' else null end) as Recurring,
                    max(case when ci.TypeID in (" . implode(', ', $atTypes) . ") then 1 else 0 end) as at201type
	            from Cart c
                join CartItem ci on c.CartID = ci.CartID
	            where
	                    c.UserID = {$userId}
                    and c.PaymentType is not null
                    and c.PayDate is not null
                    and ci.ScheduledDate is null
                    and (ci.Price * ci.Cnt * ((100-ci.Discount)/100)) <> 0
                    {$endDate}
                    {$typesCondition}
                group by c.UserID, c.CartID, c.PaymentType
                having Price > 0
            ";

            $payments = $this->connection->fetchAllAssociative($sql);

            foreach ($payments as $pay) {
                calcProfit($pay['PaymentType'], $pay['Price'], $pay['Fee'], $pay['Income']);
                $totalPays['price'] += $pay['Price'];
                $totalPays['fee'] += $pay['Fee'];
                $totalPays['income'] += $pay['Income'];

                if ($pay['Income'] > 0) {
                    ++$totalPays['countPayments'];
                }
            }

            if (!empty($payments)) {
                $this->putUserCount($userId);
            }
        }

        return $totalPays;
    }

    private function drawTable($data): string
    {
        $html = '<div style="padding:1rem;"><table class="table tstat" style="width:100%;margin:1rem;">';
        $html .= '<thead></thead>';

        $html .= '<tbody>';

        foreach ($data as $row) {
            unset($value);

            switch ($row['type'] ?? null) {
                case 'separate':
                    $html .= '</tbody></table>';
                    $html .= '<table class="table tstat" style="width:100%;margin: 1rem;"><tbody>';

                    break;

                case 'currency':
                    $value = $this->localizeService->formatCurrency((float) $row['value'], 'USD');

                    break;

                case 'string':
                    $value = $row['value'];

                    break;

                default:
                    $value = $this->localizeService->formatNumber((float) $row['value']);
            }

            if (!isset($value)) {
                continue;
            }

            $html .= '<tr' . ($row['attr'] ?? '') . '>';
            $html .= '<th>' . $row['title'] . '</th>';
            $html .= '<td>' . $value . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table></div>';

        return $html;
    }

    private function formatData($data): array
    {
        foreach ($data as &$row) {
            switch ($row['type'] ?? null) {
                case 'currency':
                    $row['value'] = $this->localizeService->formatCurrency((float) $row['value'], 'USD');

                    break;

                case 'string':
                    break;

                default:
                    $row['value'] = $this->localizeService->formatNumber((float) $row['value']);
            }
        }

        return $data;
    }

    private function putUserCount(int $userId): void
    {
        if (!in_array($userId, $this->usersCount, true)) {
            $this->usersCount[] = $userId;
        }
    }

    private function getMobileUsersCount($usersIn): int
    {
        $count = 0;
        $usersChunk = array_chunk($usersIn, 1000, true);

        foreach ($usersChunk as $users) {
            $sql = '
                SELECT COUNT(*)
                FROM Usr u
                JOIN MobileDevice md ON (u.UserID = md.UserID)
                WHERE
                        UNIX_TIMESTAMP(md.CreationDate) BETWEEN UNIX_TIMESTAMP(u.CreationDateTime) - 10 AND UNIX_TIMESTAMP(u.CreationDateTime) + 10
                    AND u.UserID IN (' . implode(',', array_keys($users)) . ')            
            ';

            $count += (int) $this->connection->fetchOne($sql);
        }

        return $count;
    }

    private function test($min, $max)
    {
        $data = $this->connection->fetchAllAssociative("
            select c.UserID, c.CartID, u.CreationDateTime, sum(ci.Price * ci.Cnt * (100 - ci.Discount)/100) as Total from Usr u join Cart c on c.UserID = u.UserID join CartItem ci on ci .CartID = c.CartID where u.CreationDateTime >= '{$min}' and u.CreationDateTime < '{$max}' and c.PayDate is not null and ci.Price <> 0 and ci.ScheduledDate is null group by c.UserID, c.CartID having Total > 0
        ");

        return [
            'sum' => array_sum(array_column($data, 'Total')),
            'data' => $data,
        ];
    }
}
