<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProviderAbstract;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\RewardsActivity;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use AwardWallet\MainBundle\Service\Account\RecentlyList;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Helper;

class RewardsActivityProvider extends DataProviderAbstract
{
    public const PERIOD = [
        RewardsActivity::PERIOD_WEEK => 'week',
        RewardsActivity::PERIOD_MONTH => 'month',
        RewardsActivity::PERIOD_DAY => 'day',
    ];

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityManager
     */
    protected $conn;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RecentlyList
     */
    protected $recently;

    /**
     * @var AdManager
     */
    protected $adManager;

    /**
     * @var int[]
     */
    protected $users = [];

    /**
     * @var int
     */
    protected $startUser;

    /**
     * @var int
     */
    protected $endUser;

    /**
     * @var int[]
     */
    protected $providers = [];

    /**
     * @var \DateTime
     */
    protected $startDate;

    /**
     * @var \DateTime
     */
    protected $endDate;

    /**
     * @var int
     */
    protected $period;

    /**
     * @var string
     */
    protected $testEmail;

    /**
     * @var bool
     */
    protected $testMode = false;

    /**
     * @var int
     */
    protected $delay;

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $query;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var UsrRepository
     */
    private $userRep;

    /**
     * @var UseragentRepository
     */
    private $uaRep;

    /**
     * @var RewardsActivity
     */
    private $message;

    private $processed = 0;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $unbufConnection,
        LoggerInterface $logger,
        RecentlyList $recently,
        AdManager $adManager
    ) {
        $this->em = $entityManager;
        $this->conn = $unbufConnection;
        $this->logger = $logger;
        $this->recently = $recently;
        $this->adManager = $adManager;

        $this->userRep = $this->em->getRepository(Usr::class);
        $this->uaRep = $this->em->getRepository(Useragent::class);
    }

    /**
     * @param \int[] $users
     * @return RewardsActivityProvider
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @param int $startUser
     * @return RewardsActivityProvider
     */
    public function setStartUser($startUser)
    {
        $this->startUser = $startUser;

        return $this;
    }

    /**
     * @param int $endUser
     * @return RewardsActivityProvider
     */
    public function setEndUser($endUser)
    {
        $this->endUser = $endUser;

        return $this;
    }

    /**
     * @param \int[] $providers
     * @return RewardsActivityProvider
     */
    public function setProviders($providers)
    {
        $this->providers = $providers;

        return $this;
    }

    /**
     * @param \DateTime $startDate
     * @return RewardsActivityProvider
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @param \DateTime $endDate
     * @return RewardsActivityProvider
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @param int $period
     * @return RewardsActivityProvider
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @param string $testEmail
     * @return RewardsActivityProvider
     */
    public function setTestEmail($testEmail)
    {
        $this->testEmail = $testEmail;

        return $this;
    }

    /**
     * @param bool $testMode
     * @return RewardsActivityProvider
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;

        return $this;
    }

    /**
     * @param int $delay
     * @return RewardsActivityProvider
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    public function next()
    {
        if (empty($this->query)) {
            $this->executeSQL();
        }

        $this->fields = $this->query->fetch(\PDO::FETCH_ASSOC);
        $result = $this->fields !== false;
        $this->processed++;

        if (($this->processed % 100) == 0) {
            $this->em->clear();
            $this->logger->info("processed {$this->processed} users, mem: " . Helper::formatMemory(memory_get_usage(true)));
        }

        if ($result) {
            $conditionProvId = "";

            if (!empty($this->providers)) {
                $conditionProvId = " AND a.ProviderID IN (" . implode(", ", $this->providers) . ")";
            }
            $this->message = new RewardsActivity();
            $this->message->period = $this->period;
            $this->message->fromDate = $this->startDate;
            $this->message->toDate = $this->endDate;
            $user = $this->userRep->find($this->fields['UserID']);

            if ($this->fields['Connected'] != '0') {
                $fm = $this->uaRep->find($this->fields['UserAgentID']);

                if ($fm) {
                    $this->message->toFamilyMember($fm);
                    $this->message->accounts = $this->recently->getAccounts(
                        $user,
                        $fm,
                        $this->message->fromDate,
                        $this->message->toDate,
                        $conditionProvId
                    );
                }
            } else {
                if ($this->fields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
                    $admin = $this->userRep->findOneByEmail($this->fields['Email']);

                    if ($admin) {
                        $this->message->toUser($admin, true);
                        $this->message->businessRecipient = $user;
                    }
                } else {
                    $this->message->toUser($user, false);
                }
                $this->message->accounts = $this->recently->getAccounts(
                    $user,
                    $this->fields["EmailFamilyMemberAlert"] == '0' ? 'my' : null,
                    $this->message->fromDate,
                    $this->message->toDate,
                    $conditionProvId
                );
            }
            $providers = [];

            foreach ($this->message->accounts as $account) {
                if (isset($account['Kind']) && isset($account['ProviderID']) && isset($account['ChangeCount'])) {
                    if (!isset($providers[$account['ProviderID']])) {
                        $providers[$account['ProviderID']] = [
                            'Kind' => $account['Kind'],
                            'ChangeCount' => $account['ChangeCount'],
                        ];
                    } elseif ($providers[$account['ProviderID']]['ChangeCount'] < $account['ChangeCount']) {
                        $providers[$account['ProviderID']]['ChangeCount'] = $account['ChangeCount'];
                    }
                }
            }

            $this->message->advt = $this->getAd($user, $providers);
            $this->message->setDebug(isset($this->testEmail));
        }

        return $result;
    }

    public function canSendEmail()
    {
        $this->logger->debug(
            sprintf(
                "processing userId: %d (%s)",
                $this->fields['UserID'],
                $this->fields['FirstName'] . " " . $this->fields['LastName']
            )
        );

        if ($this->testMode) {
            $this->logger->info(sprintf("test mode, skip sending to %s, userId %d", $this->getEmail(), $this->fields['UserID']));

            return false;
        }

        if (!sizeof($this->message->accounts)) {
            $this->logger->info(sprintf("userId: %d, no accounts", $this->fields['UserID']));

            return false;
        }

        return true;
    }

    public function getOptions()
    {
        $options = parent::getOptions();

        if (isset($this->testEmail)) {
            return array_merge($options, [
                Mailer::OPTION_SKIP_DONOTSEND => true,
                Mailer::OPTION_SKIP_STAT => true,
            ]);
        }

        return $options;
    }

    public function preSend(Mailer $mailer, \Swift_Message $message, &$options, bool $dryRun = false)
    {
        $this->logger->info(sprintf("mailing to %s", key($message->getTo())));

        foreach ($this->message->accounts as $account) {
            $this->logger->info(
                sprintf(
                    "accountId: %d - %s, %s, from: %s to: %s, change: %s",
                    $account['ID'],
                    isset($account['SubDisplayName']) && !empty($account['SubDisplayName']) ?
                        html_entity_decode($account['DisplayName'] . " (" . $account['SubDisplayName'] . ")") :
                        html_entity_decode($account['DisplayName']),
                    html_entity_decode($account['UserName']),
                    $account['LastBalance'],
                    $account['Balance'],
                    $account['LastChange']
                )
            );
        }
    }

    public function getMessage(Mailer $mailer)
    {
        $this->message->setEmail($this->getEmail());

        return $mailer->getMessageByTemplate($this->message);
    }

    public function postSend(Mailer $mailer, \Swift_Message $message, $options, $sendResult, $dryRun = false)
    {
        if (isset($this->delay) && $this->delay > 0) {
            sleep($this->delay);
        }
    }

    public function getEmail()
    {
        if (isset($this->testEmail)) {
            return $this->testEmail;
        } else {
            return $this->fields['Email'];
        }
    }

    public function reset()
    {
        $this->processed = 0;
        $this->executeSQL();
    }

    /**
     * @return int
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    protected function getAd(Usr $user, array $providers = [])
    {
        $opt = new Options(ADKIND_EMAIL, $user, RewardsActivity::getEmailKind());
        $opt->flatData = $providers;

        return $this->adManager->getAdvt($opt);
    }

    protected function executeSQL()
    {
        $conn = $this->conn;
        $queryParams = [
            [':providerStateEnable', PROVIDER_ENABLED, \PDO::PARAM_INT],
            [':providerStateTest', PROVIDER_TEST, \PDO::PARAM_INT],
            [':accountState', ACCOUNT_ENABLED, \PDO::PARAM_INT],
            [':emailRewards', $this->period, \PDO::PARAM_INT],
            [':accountLevelBusiness', ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT],
            [':startDate', $this->startDate->format("Y-m-d H:i:s"), \PDO::PARAM_STR],
            [':endDate', $this->endDate->format("Y-m-d H:i:s"), \PDO::PARAM_STR],
            [':admin', ACCESS_ADMIN, \PDO::PARAM_INT],
        ];

        $filter = "";

        if (isset($this->startUser)) {
            $filter .= " AND u.UserID >= :startUserId";
            $queryParams[] = [':startUserId', $this->startUser, \PDO::PARAM_INT];
        }

        if (isset($this->endUser)) {
            $filter .= " AND u.UserID < :endUserId";
            $queryParams[] = [':endUserId', $this->endUser, \PDO::PARAM_INT];
        }

        if (!empty($this->providers)) {
            $filter .= " AND a.ProviderID IN (:providerIds)";
            $queryParams[] = [':providerIds', $this->providers, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        }

        if (!empty($this->users)) {
            $filter .= " AND u.UserID IN (:userIds)";
            $queryParams[] = [':userIds', $this->users, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        }

        $sql = "
        select distinct
			u.UserID, null as UserAgentID, u.FirstName, u.LastName, u.Email, '0' as Connected, u.EmailFamilyMemberAlert, u.AccountLevel
		from
			Account a
			join Provider p on a.ProviderID = p.ProviderID
			join Usr u on a.UserID = u.UserID
			left join SubAccount sa on a.AccountID = sa.AccountID
			left join UserAgent ua on a.UserAgentID = ua.UserAgentID
		where
			(p.State >= :providerStateEnable or p.State = :providerStateTest)
			and a.State >= :accountState
			and (
			    (sa.SubAccountID is not null and (sa.Kind is null or sa.Kind <> 'C') and sa.ChangeCount > 0)
			    or (sa.SubAccountID is null and a.ChangeCount > 0)
            )
	 		and u.EmailRewards = :emailRewards
			and u.AccountLevel <> :accountLevelBusiness
			and (u.EmailFamilyMemberAlert = 1 or a.UserAgentID is null or ua.Email is null or ua.SendEmails = 0 or ua.Email = u.Email)
			and a.AccountID in (select AccountID from AccountBalance where UpdateDate >= :startDate and UpdateDate <= :endDate)
			$filter
		
		union
		
		select distinct
            a.UserID, a.UserAgentID, ua.FirstName, ua.LastName, ua.Email, '1' as Connected, u.EmailFamilyMemberAlert, u.AccountLevel
        from
            Account a
			join Provider p on a.ProviderID = p.ProviderID
			join Usr u on a.UserID = u.UserID
			left join SubAccount sa on a.AccountID = sa.AccountID
			left join UserAgent ua on a.UserAgentID = ua.UserAgentID
		where
			(p.State >= :providerStateEnable or p.State = :providerStateTest)
			and a.State >= :accountState
			and (
			    (sa.SubAccountID is not null and (sa.Kind is null or sa.Kind <> 'C') and sa.ChangeCount > 0)
			    or (sa.SubAccountID is null and a.ChangeCount > 0)
            )
	 		and u.EmailRewards = :emailRewards
			and (a.UserAgentID is not null and ua.SendEmails = 1 and ua.Email is not null and ua.Email <> u.Email)
			and a.AccountID in (select AccountID from AccountBalance where UpdateDate >= :startDate and UpdateDate <= :endDate)
			$filter
        
        union
        
        select distinct
            a.UserID, null as UserAgentID, u2.FirstName, u2.LastName, u2.Email, '0' as Connected, u.EmailFamilyMemberAlert, u.AccountLevel
        from
            Account a
			join Provider p on a.ProviderID = p.ProviderID
			join Usr u on a.UserID = u.UserID
			left join SubAccount sa on a.AccountID = sa.AccountID
			join UserAgent ua on a.UserID = ua.ClientID
			join Usr u2 on ua.AgentID = u2.UserID
			left join UserAgent ua2 on a.UserAgentID = ua2.UserAgentID
		where
			(p.State >= :providerStateEnable or p.State = :providerStateTest)
			and a.State >= :accountState
			and (
			    (sa.SubAccountID is not null and (sa.Kind is null or sa.Kind <> 'C') and sa.ChangeCount > 0)
			    or (sa.SubAccountID is null and a.ChangeCount > 0)
            )
	 		and u.EmailRewards = :emailRewards
			and (u.EmailFamilyMemberAlert = 1 or a.UserAgentID is null or ua2.Email is null or ua2.SendEmails = 0 or ua2.Email = u2.Email)
			and u.AccountLevel = :accountLevelBusiness
			and ua.AccessLevel = :admin
			and a.AccountID in (select AccountID from AccountBalance where UpdateDate >= :startDate and UpdateDate <= :endDate)
			$filter
		
        order by  UserID, FirstName, LastName, Email
        ";

        $params = [];
        $types = [];

        foreach ($queryParams as [$name, $value, $type]) {
            $params[$name] = $value;
            $types[$name] = $type;
        }

        $this->query = $conn->executeQuery($sql, $params, $types);
    }
}
