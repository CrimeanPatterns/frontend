<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\EmailTemplate\QueryBuilderWithMetaGroupBy;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UserEventStatCommand extends Command
{
    public const DATE_PERIODS = [
        // 2022
        ['start' => '2022-05-01 00:00:00', 'end' => '2022-05-31 23:59:59', 'title' => 'May 2022'],
        ['start' => '2022-01-01 00:00:00', 'end' => '2022-01-31 23:59:59', 'title' => 'Jan 2022'],
        // 2021
        ['start' => '2021-07-01 00:00:00', 'end' => '2021-07-31 23:59:59', 'title' => 'Jul 2021'],
        ['start' => '2021-01-01 00:00:00', 'end' => '2021-12-31 23:59:59', 'title' => 'Full 2021'],

        // test
        // ['start' => '2022-05-01 00:00:00', 'end' => '2022-05-03 23:59:59', 'title' => 'test-May 2022'],
    ];
    protected static $defaultName = 'aw:stats:user-events';

    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $stat = [];

        foreach (self::DATE_PERIODS as $period) {
            $users = [
                'all' => [],
                'ref' => [],
                'us' => [],
                'us_ref' => [],
            ];

            $start = $period['start'];
            $end = $period['end'];
            $key = $period['title'] . ' (' . $start . ' - ' . $end . ')';

            $all = $this->connection->fetchAllAssociative("
                SELECT
                        u.UserID, u.CameFrom, u.Referer, u.CreationDateTime, /*u.Accounts,*/ u.ValidMailboxesCount, u.EmailOffers,
                        md.UserID as mdUserID,
                        qtClick.UserID as qsClickUserID,
                        qtApproval.UserID as qtApprovalUserID,
                        MIN(a.CreationDate) as MinAccountCreationDate, COUNT(a.AccountID) as Accounts
                FROM Usr u
                LEFT JOIN MobileDevice md ON md.UserID = u.UserID
                     AND md.DeviceType IN (" . implode(',', [MobileDevice::TYPE_ANDROID, MobileDevice::TYPE_IOS]) . ")
                LEFT JOIN QsTransaction qtClick ON qtClick.UserID = u.UserID
                LEFT JOIN QsTransaction qtApproval ON qtApproval.UserID = u.UserID AND qtApproval.Approvals > 0
                LEFT JOIN Account a ON (a.UserID = u.UserID) 
                WHERE u.CreationDateTime BETWEEN '" . $start . "' AND '" . $end . "'
                GROUP BY u.UserID
            ");

            $checkCount = (int) $this->connection->fetchOne("
                SELECT COUNT(*)
                FROM Usr
                WHERE CreationDateTime BETWEEN '" . $start . "' AND '" . $end . "'
            ");

            if (count($all) !== $checkCount) {
                throw new \Exception('Error COUNT users');
            }

            foreach ($all as $user) {
                $users['all'][] = $user;

                if ($this->isRefererUser($user)) {
                    $users['ref'][] = $user;
                }
            }

            $builder = $this->getBuilderForUsUsers($period);
            $allUs = $this->connection->fetchAllAssociative($builder->getSQL());

            $groupUserAccounts = [];
            $allUsUserIds = array_column($allUs, 'UserID');

            foreach (array_chunk($allUsUserIds, 10000) as $usersIds) {
                $userAccounts = $this->connection->fetchAllAssociative('
                    SELECT UserID, MIN(CreationDate) as MinCreationDate, COUNT(*) as CountAccounts  
                    FROM Account
                    WHERE UserID IN (' . implode(',', $usersIds) . ')
                    GROUP BY UserID
                ');
                $userAccounts = array_column($userAccounts, null, 'UserID');
                $groupUserAccounts = array_replace($groupUserAccounts, $userAccounts);
            }

            foreach ($allUs as $user) {
                $userId = (int) $user['UserID'];

                if (array_key_exists($userId, $groupUserAccounts)) {
                    $user['MinAccountCreationDate'] = $groupUserAccounts[$userId]['MinCreationDate'];
                    $user['Accounts'] = $groupUserAccounts[$userId]['CountAccounts'];
                } else {
                    $user['MinAccountCreationDate'] = '';
                    $user['Accounts'] = 0;
                }

                $users['us'][] = $user;

                if ($this->isRefererUser($user)) {
                    $users['us_ref'][] = $user;
                }
            }

            $stat[$key]['registered'] = [
                'all' => \count($users['all']),
                'all_ref' => \count($users['ref']),
                'us' => \count($users['us']),
                'us_ref' => \count($users['us_ref']),
            ];

            $fn = fn ($item) => (int) $item['Accounts'] > 0;
            $stat[$key]['addedAccountsUsers'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];

            $fn = fn ($item) => !empty($item['mdUserID']);
            $stat[$key]['mobileDeviceUsers'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];

            $fn = fn ($item) => (int) $item['ValidMailboxesCount'] > 0;
            $stat[$key]['mailboxesUsers'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];

            $fn = fn ($item) => !empty($item['qsClickUserID']);
            $stat[$key]['cardClicksUsers'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];

            $fn = fn ($item) => !empty($item['qtApprovalUserID']);
            $stat[$key]['cardApprovalsUsers'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];

            $fn = fn ($item) => (int) $item['EmailOffers'] === 0;
            $stat[$key]['unsubscibePromoEmailOfferUsers'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];

            $qs = [
                'all' => $this->fetchEarnings(array_column($users['all'], 'UserID')),
                'all_ref' => $this->fetchEarnings(array_column($users['ref'], 'UserID')),
                'us' => $this->fetchEarnings(array_column($users['us'], 'UserID')),
                'us_ref' => $this->fetchEarnings(array_column($users['us_ref'], 'UserID')),
            ];
            $stat[$key]['cardApprovals'] = [
                'all' => $qs['all']['sumApprovals'] ?? 0,
                'all_ref' => $qs['all_ref']['sumApprovals'] ?? 0,
                'us' => $qs['us']['sumApprovals'] ?? 0,
                'us_ref' => $qs['us_ref']['sumApprovals'] ?? 0,
            ];

            $stat[$key]['cardEarnings'] = [
                'all' => $qs['all']['sumEarnings'] ?? 0,
                'all_ref' => $qs['all_ref']['sumEarnings'] ?? 0,
                'us' => $qs['us']['sumEarnings'] ?? 0,
                'us_ref' => $qs['us_ref']['sumEarnings'] ?? 0,
            ];

            $fn = function ($item) {
                if (empty($item['MinAccountCreationDate'])) {
                    return false;
                }
                $userCreationDate = strtotime($item['CreationDateTime']);
                $firstAccountCreationDate = strtotime($item['MinAccountCreationDate']);

                if (($firstAccountCreationDate - $userCreationDate) > 30 * 86400) {
                    return false;
                }

                return true;
            };
            $stat[$key]['firstAccountLess1MonthAdded'] = [
                'all' => it($users['all'])->filter($fn)->count(),
                'all_ref' => it($users['ref'])->filter($fn)->count(),
                'us' => it($users['us'])->filter($fn)->count(),
                'us_ref' => it($users['us_ref'])->filter($fn)->count(),
            ];
        }

        $html = '';

        foreach ($stat as $key => $period) {
            $html .= '<h3>' . $key . '</h3>';

            $html .= '<table border="1" cellpadding="4" style="border-collapse: collapse;border: solid 1px #333;">';
            $html .= '<tr>';
            $html .= '<td></td><td>All</td><td>All Referals</td><td>U.S.</td><td>U.S. Referals</td>';
            $html .= '</tr>';

            foreach ($period as $type => $values) {
                $html .= '<tr>';
                $html .= '<td>' . $type . '</td>';

                foreach ($values as $value) {
                    $html .= '<td>' . $value . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</table><hr>';
        }

        $output->writeln([PHP_EOL, $html, PHP_EOL]);

        return 0;
    }

    protected function configure()
    {
        $this->setDescription('Stats');
    }

    private function isRefererUser(array $user): bool
    {
        if (!empty($user['CameFrom'])) {
            return true;
        }

        if (!empty($user['Referer'])) {
            $ref = parse_url($user['Referer']);

            if (!empty($ref['host']) && 'awardwallet.com' !== $ref['host']) {
                return true;
            }
        }

        return false;
    }

    private function getBuilderForUsUsers(array $datePeriod): QueryBuilderWithMetaGroupBy
    {
        $builder = new QueryBuilderWithMetaGroupBy($this->connection);
        $metaGroupBy = [];
        $metaSelect = [];
        $userTablePrefix = 'u';
        $e = $builder->expr();
        $countryWhere = [];
        $where = [];

        $builder->select([
            'u.UserID /* Id юзера (или админа бизнеса) */',
            'u.FirstName /* Имя юзера (или админа бизнеса) */',
            'u.LastName /* Фамилия юзера (или админа бизнеса) */',
            'u.Email /* Email юзера (или админа бизнеса) */',
            'u.Login /* Login юзера (или админа бизнеса) */',
            'u.RegistrationIP /* IP юзера при регистрации (или админа бизнеса) */',
            'u.LastLogonIP /* IP юзера при последнем логине (или админа бизнеса) */',
            'u.RefCode /* Реферальный код */',
            'u.CameFrom',
            'u.Referer',
            'u.CreationDateTime',
            // 'u.Accounts ',
            'u.ValidMailboxesCount',
            'u.EmailOffers',
            'md.UserID as mdUserID',
            'qtClick.UserID as qsClickUserID',
            'qtApproval.UserID as qtApprovalUserID',
            'IF (
                u.ZipCodeUpdateDate is not null and
                u.ZipCodeAccountID is not null and
                u.ZipCodeProviderID is not null and
                trim(u.Zip) regexp \'^[0-9]{5}([^0-9]*[0-9]{4})?$\',
                substr(trim(u.Zip), 1, 5),
                null
            ) as Zip /* Zip-code (5-digits) */',
            // 'MIN(a.CreationDate) as MinAccountCreationDate',
        ]);

        $builder->from('Usr', $userTablePrefix);

        $builder->leftJoin('u', 'MobileDevice', 'md', $e->eq('u.UserID', 'md.UserID'));
        $builder->leftJoin('u', 'QsTransaction', 'qtClick', $e->eq('u.UserID', 'qtClick.UserID'));
        $builder->leftJoin('u', 'QsTransaction', 'qtApproval', $e->and(
            $e->eq('u.UserID', 'qtApproval.UserID'),
            $e->eq('qtApproval.Approvals', '1')
        ));
        // $builder->leftJoin('u', 'Account', 'a', $e->eq('u.UserID', 'a.UserID'));

        $builder->where(
            $e->and(
                $e->gte('u.CreationDateTime', "'" . $datePeriod['start'] . "'"),
                $e->lte('u.CreationDateTime', "'" . $datePeriod['end'] . "'")
            )
        );

        $builder->leftJoin('u', 'Country', 'c', $e->eq('u.CountryID', 'c.CountryID'));

        $countryWhere[] = $e->eq('c.CountryID', Country::UNITED_STATES);
        $where[] = $e->or(...$countryWhere);

        $builder->addSelect('SUM(IF(' . $e->or(...$where) . ', 1, 0)) as DataUsers2_Country');

        $builder
            ->addSelect("
                SUM(IF(
                        account_us_users_2.ProviderID IN (103, 106, 75, 98, 87)
                        OR (
                                account_us_users_2.ProviderID = 84
                            AND account_us_users_2.Login2 = 'United States'
                        )
                        OR (
                            account_us_users_2.ProviderID = 364
                            AND (
                                account_us_users_2.Login2 = 'USA' OR
                                account_us_users_2.Login2 = '' OR
                                account_us_users_2.Login2 is null
                            )
                        )
                        OR (
                            account_us_users_2.ProviderID = 104
                            AND (
                                account_us_users_2.Login2 = 'US' OR
                                account_us_users_2.Login2 = '' OR
                                account_us_users_2.Login2 is null
                            )
                        )
                        OR (
                            account_us_users_2.ProviderID = 123 AND
                            account_us_users_2.Login2 = 'USA'
                        ),
                        1,
                        0    
                )) as DataUsers2_ConnectedAccounts
            ")
            ->leftJoin($userTablePrefix, 'Account', 'account_us_users_2',
                $e->and(
                    $e->eq("{$userTablePrefix}.UserID", 'account_us_users_2.UserID'),
                    $e->in("account_us_users_2.ProviderID", [103, 106, 75, 98, 87, 84, 364, 104, 123])
                )
            );

        $builder
            ->addSelect("SUM(IF(user_credit_card_us_users_2.UserID IS NOT NULL, 1, 0 )) as DataUsers2_CreditCards")
            ->leftJoin($userTablePrefix, 'UserCreditCard', 'user_credit_card_us_users_2',
                $e->eq("{$userTablePrefix}.UserID", 'user_credit_card_us_users_2.UserID')
            );

        $builder
            ->addSelect("SUM(IF(qs_transactions_us_users_2.UserID IS NOT NULL, 1, 0 )) as DataUsers2_QsTransactions")
            ->leftJoin($userTablePrefix, 'QsTransaction', 'qs_transactions_us_users_2',
                $e->and(
                    $e->eq("{$userTablePrefix}.UserID", 'qs_transactions_us_users_2.UserID'),
                    $e->gt('qs_transactions_us_users_2.applications', 0)
                )
            );

        $builder
            ->addSelect("SUM(IF(
                    {$userTablePrefix}.ZipCodeUpdateDate is not null and
                    trim({$userTablePrefix}.Zip) regexp '^[0-9]{5}([^0-9]*[0-9]{4})?$'
                , 1, 0
                )) as DataUsers2_Zip"
            );

        $builder->andHaving("(
               DataUsers2_ConnectedAccounts > 0
            OR DataUsers2_CreditCards > 0
            OR DataUsers2_QsTransactions > 0
            OR DataUsers2_Zip > 0
            OR DataUsers2_Country > 0
        )");

        $groupBy = [
            'UserID',
            'FirstName',
            'LastName',
            'Email',
            'Login',
            'RegistrationIP',
            'LastLogonIP',
            'RefCode',
            'ZipCodeUpdateDate',
            'ZipCodeAccountID',
            'ZipCodeProviderID',
            'Zip',
        ];

        $builder->groupBy($groupBy);
        $builder->setMetaGroupBy($metaGroupBy);
        $builder->setMetaSelect($metaSelect);

        return $builder;
    }

    private function fetchEarnings(array $userIds)
    {
        if (empty($userIds)) {
            return [
                'sumApprovals' => 0,
                'sumEarnings' => 0,
            ];
        }

        return $this->connection
            ->fetchAssociative('
                SELECT SUM(Approvals) as sumApprovals, SUM(Earnings) as sumEarnings
                FROM QsTransaction
                WHERE UserID IN (' . implode(',', $userIds) . ') AND Approvals > 0
            ');
    }
}
