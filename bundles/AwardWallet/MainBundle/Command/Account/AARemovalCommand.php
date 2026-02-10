<?php

namespace AwardWallet\MainBundle\Command\Account;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\AARemoval;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AARemovalCommand extends Command
{
    public const CHANGE_ORG_LINK = 'https://www.change.org/AA-AwardWallet';

    public const ACCOUNTS_CHUNK_LIMIT = 5000;
    public const SQL_DATE_REMOVE_AFTER = 'NOW()';

    private const AA_PROPERTY_KIND_LASTNAME = 4358;
    public static $defaultName = 'aw:aa-account-removal';

    private LoggerInterface $logger;
    private Mailer $mailer;
    private EntityManagerInterface $entityManager;
    private LocalizeService $localizeService;

    private $testEmail;

    public function __construct(
        LoggerInterface $logger,
        Mailer $mailer,
        EntityManagerInterface $entityManager,
        LocalizeService $localizeService
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->localizeService = $localizeService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove AA accounts and future segments')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'run test')
            ->addOption('email', 'm', InputOption::VALUE_REQUIRED, 'test email instead real address')
            ->addOption('userId', 'u', InputOption::VALUE_OPTIONAL, 'filter by userId')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit by user')
            ->addOption('startUserId', 's', InputOption::VALUE_OPTIONAL, 'start userId')
            ->addOption('endUserId', 'd', InputOption::VALUE_OPTIONAL, 'end userId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->entityManager->getRepository(Usr::class);

        $isTest = (bool) $input->getOption('test');
        $onlyUserId = (int) $input->getOption('userId');
        $limit = (int) $input->getOption('limit');
        $this->testEmail = $input->getOption('email');
        $counter = ['users' => 0, 'accounts' => 0, 'segments' => 0];

        $where = empty($onlyUserId) ? '' : ' AND u.UserID = ' . $onlyUserId . ' ';

        $startUserId = (int) $input->getOption('startUserId');
        $endUserId = (int) $input->getOption('endUserId');

        if (!empty($startUserId) || !empty($endUserId)) {
            if (empty($startUserId) || empty($endUserId)) {
                throw new \Exception('START and END userId must be specified');
            }

            $where .= ' AND (u.UserID >= ' . $startUserId . ' AND u.UserID <= ' . $endUserId . ') ';
        }
        $users = $this->fetchUserAccounts($where, $limit);

        foreach ($users as $userId => $accounts) {
            $counter['users']++;
            /** @var Usr $user */
            $user = $userRepository->find($userId);
            $output->writeln('UserID: ' . $user->getId() . ', ' . $user->getFullName());

            $accountsId = array_column($accounts, 'AccountID');
            $accountsData = [];

            foreach ($accounts as $account) {
                $isFamilyMember = !empty($account['UserAgentID']);
                $accountsData[] = [
                    'owner' => !$isFamilyMember ? $user->getFullName() : trim($account['ua_FirstName'] . ' ' . $account['ua_LastName']),
                    'aa_number' => array_key_exists(PROPERTY_KIND_NUMBER, $account['properties'])
                        ? $account['properties'][PROPERTY_KIND_NUMBER]
                        : $account['Login'],
                    'lastname' => array_key_exists('_lastname', $account['properties'])
                        ? $account['properties']['_lastname']
                        : '',
                    'expiration' => !empty($account['ExpirationDate'])
                        ? $this->localizeService->formatDate(new \DateTime($account['ExpirationDate']), 'long')
                        : '',
                    'level' => array_key_exists(PROPERTY_KIND_STATUS, $account['properties'])
                        ? $account['properties'][PROPERTY_KIND_STATUS]
                        : '',
                ];
            }

            if (!$isTest) {
                $query = $this->entityManager->getConnection()->executeQuery(
                    'DELETE FROM Account WHERE AccountID IN (:accountsId)',
                    ['accountsId' => $accountsId],
                    ['accountsId' => Connection::PARAM_INT_ARRAY]
                );
                $isAccountRemoved = $query->execute();
            }
            $counter['accounts'] += count($accountsId);
            $output->writeln(' - accounts ['
                . implode(', ', $accountsId) . '] remove executed: '
                . var_export($isAccountRemoved ?? false, true)
            );

            // --

            $tripSegments = $this->entityManager->getConnection()->fetchAllAssociative('
                        SELECT
                                ts.TripSegmentID, ts.DepDate, ts.DepCode, ts.ArrCode,
                                t.TripID
                        FROM TripSegment ts
                        JOIN Trip t ON (ts.TripID = t.TripID)
                        WHERE
                                t.UserID IN (:usersId)
                            AND (
                                    t.AccountID IN (:accountsId)
                                OR (t.ProviderID = :providerId AND t.ConfFields IS NOT NULL)
                                OR ts.AirlineID = 39
                            )
                            AND ts.DepDate > ' . self::SQL_DATE_REMOVE_AFTER . '
                        ',
                [
                    'usersId' => [$userId],
                    'providerId' => Provider::AA_ID,
                    'accountsId' => $accountsId,
                ],
                [
                    'usersId' => Connection::PARAM_INT_ARRAY,
                    'providerId' => \PDO::PARAM_INT,
                    'accountsId' => Connection::PARAM_INT_ARRAY,
                ]
            );

            foreach ($tripSegments as $tripSegment) {
                $this->logger->info(' - trip segment TripID:' . $tripSegment['TripID'] . ', TripSegmentID:' . $tripSegment['TripSegmentID'] . ', ' . $tripSegment['DepDate'] . ', Route:' . $tripSegment['DepCode'] . '-' . $tripSegment['ArrCode'],
                    $tripSegment);
            }

            if (!$isTest) {
                $query = $this->entityManager->getConnection()->executeQuery(
                    'DELETE FROM TripSegment WHERE TripSegmentID IN (:tripSegmentId)',
                    ['tripSegmentId' => array_column($tripSegments, 'TripSegmentID')],
                    ['tripSegmentId' => Connection::PARAM_INT_ARRAY]
                );
                $isSegmentRemoved = $query->execute();
            }
            $counter['segments'] += count($tripSegments);
            $output->writeln(' - tripsegment remove executed: '
                . var_export($isSegmentRemoved ?? false, true)
            );

            // ==

            $templateVars = [
                'accounts' => $accountsData,
                'isTripsFound' => count($tripSegments) > 0,
                'changeOrgLink' => self::CHANGE_ORG_LINK,
                'helloName' => $user->getFirstname(),
            ];

            if ($user->isBusiness()) {
                $admins = $userRepository->getBusinessAdmins($user);

                /** @var Usr $admin */
                foreach ($admins as $admin) {
                    $templateVars['helloName'] = $admin->getFullName();
                    $message = $this->getEmailMessage($admin, $templateVars);
                    $this->mailer->send($message);
                }
            } else {
                $message = $this->getEmailMessage($user, $templateVars);
                $this->mailer->send($message);
            }
        }

        $output->writeln('==');
        $output->writeln('Total AA Removed - users:' . $counter['users'] . ', accounts:' . $counter['accounts'] . ', segments:' . $counter['segments']);

        return 0;
    }

    private function getEmailMessage(Usr $user, array $vars): Message
    {
        $template = new AARemoval($user);

        foreach ($vars as $key => $values) {
            $template->{$key} = $values;
        }

        if (!empty($this->testEmail)) {
            $template->setDebug(false);
        }

        $message = $this->mailer->getMessageByTemplate($template);

        if (!empty($this->testEmail)) {
            $message->setTo($this->testEmail);
        }

        return $message;
    }

    private function fetchUserAccounts(string $where = '', int $limit = 0): array
    {
        $accounts = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    a.AccountID, a.UserID, a.Login, a.ExpirationDate, a.UserAgentID,
                    u.FirstName, u.LastName, u.MidName,
                    ua.FirstName AS ua_FirstName, ua.LastName AS ua_LastName, ua.MidName as ua_MidName
            FROM Account a
            JOIN Usr u ON (u.UserID = a.UserID
                               -- AND u.AccountLevel <> ' . ACCOUNT_LEVEL_BUSINESS . '
                           )
            LEFT JOIN UserAgent ua ON (ua.UserAgentID = a.UserAgentID)
            WHERE
                    a.ProviderID = :providerId
                AND a.State IN (' . ACCOUNT_ENABLED . ',' . ACCOUNT_DISABLED . ')
                ' . $where . '
            ORDER BY u.UserID ASC
            ' . (empty($limit) ? '' : 'LIMIT ' . $limit),
            ['providerId' => Provider::AA_ID],
            ['providerId' => \PDO::PARAM_INT]
        );
        $this->logger->info('Found Accounts with state(enabled|disabled): ' . count($accounts));
        $this->logger->info('SQL extend where condition: ' . $where);

        $users = [];
        $properties = [];
        $accountsChunk = array_chunk($accounts, self::ACCOUNTS_CHUNK_LIMIT);

        foreach ($accountsChunk as $accounts) {
            $accountsId = array_column($accounts, 'AccountID');
            $pp = $this->entityManager->getConnection()->fetchAllAssociative('
                SELECT
                        ap.AccountID, ap.Val,
                        pp.Kind, pp.ProviderPropertyID
                FROM AccountProperty ap
                JOIN ProviderProperty pp ON (
                        ap.ProviderPropertyID = pp.ProviderPropertyID
                    AND (pp.Kind IN (:kind) OR pp.ProviderPropertyID = ' . self::AA_PROPERTY_KIND_LASTNAME . ')
                )
                WHERE ap.AccountID IN (:accountsId)',
                [
                    'accountsId' => $accountsId,
                    'kind' => [PROPERTY_KIND_NUMBER, PROPERTY_KIND_STATUS],
                ],
                [
                    'accountsId' => Connection::PARAM_INT_ARRAY,
                    'kind' => Connection::PARAM_INT_ARRAY,
                ]
            );

            foreach ($pp as $property) {
                $accountId = (int) $property['AccountID'];

                if (!array_key_exists($accountId, $properties)) {
                    $properties[$accountId] = [];
                }

                if (self::AA_PROPERTY_KIND_LASTNAME == $property['ProviderPropertyID']) {
                    $property['Kind'] = '_lastname';
                }
                $properties[$accountId][$property['Kind']] = $property['Val'];
            }

            foreach ($accounts as $account) {
                $userId = (int) $account['UserID'];
                $accountId = (int) $account['AccountID'];

                if (!array_key_exists($userId, $users)) {
                    $users[$userId] = [];
                }
                $account['properties'] = $properties[$accountId] ?? [];
                $users[$userId][] = $account;
            }
        }

        $this->logger->info('Count users: ' . count($users));

        return $users;
    }
}
