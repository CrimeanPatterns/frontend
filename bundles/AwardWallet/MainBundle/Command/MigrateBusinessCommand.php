<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Providercouponshare;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\AccountshareRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateBusinessCommand extends Command
{
    protected static $defaultName = 'aw:migrate-business';
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var UsrRepository
     */
    protected $users;
    /**
     * @var AccountRepository
     */
    protected $accounts;
    /**
     * @var ObjectRepository
     */
    protected $coupons;
    /**
     * @var UseragentRepository
     */
    protected $agents;
    /**
     * @var AccountshareRepository
     */
    protected $accountShare;
    /**
     * @var ObjectRepository
     */
    protected $couponShare;
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var \TSchemaManager
     */
    protected $schemaManager;
    /**
     * @var bool
     */
    private $ignoreAccountLimit = false;

    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        parent::__construct();
        $this->connection = $entityManager->getConnection();
        $this->users = $entityManager->getRepository(Usr::class);
        $this->accounts = $entityManager->getRepository(Account::class);
        $this->coupons = $entityManager->getRepository(Providercoupon::class);
        $this->agents = $entityManager->getRepository(Useragent::class);
        $this->accountShare = $entityManager->getRepository(Accountshare::class);
        $this->couponShare = $entityManager->getRepository(Providercouponshare::class);
        $this->em = $entityManager;

        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    public function configure()
    {
        $this
            ->setDescription("migrate small businesses to personal")
            ->setDefinition([
                new InputOption('ignore-account-limit', null, InputOption::VALUE_NONE, 'ignore personal account limit of ' . PERSONAL_INTERFACE_MAX_ACCOUNTS),
                new InputOption('user', null, InputOption::VALUE_REQUIRED, 'migrate only this business user id'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        require_once __DIR__ . "/../../../../web/kernel/TSchemaManager.php";
        $this->schemaManager = new \TSchemaManager();
        $this->ignoreAccountLimit = !empty($input->getOption('ignore-account-limit'));

        $filters = "";

        if (!empty($input->getOption('user'))) {
            $filters .= " and b.UserID = " . intval($input->getOption('user'));
        }
        $sql = "select
			b.UserID, b.Login, b.Company, count(distinct a.AccountID) as Accounts, count(distinct ua.UserAgentID) as Agents
		from
			Usr b
			left outer join Account a on b.UserID = a.UserID
			left outer join UserAgent ua on b.UserID = ua.AgentID
		where
			b.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . "
			$filters
		group by
			b.UserID, b.Login, b.Company";

        if (empty($input->getOption('user'))) {
            $sql .= " having count(distinct ua.UserAgentID) <= 5";
        }

        $q = $this->connection->executeQuery($sql);
        $deleted = 0;

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $business = $this->users->find($row['UserID']);
            $admins = $this->users->getBusinessAdmins($business);

            if (count($admins) != 1) {
                $output->writeln("expected 1 business admin for business {$business->getUserid()}, found: " . count($admins));

                continue;
            }
            /** @var Usr $user */
            $user = array_pop($admins);
            $output->writeln("{$business->getUserid()}:{$business->getCompany()} -> {$user->getUserid()}:{$user->getFirstname()} {$user->getLastname()}, accounts: {$row['Accounts']}, connections: {$row['Agents']}");

            if ($this->canMigrate($business, $user)) {
                $this->migrateUser($business, $user);
                $deleted++;
            }
        }
        $this->output->writeln("done, deleted {$deleted} businesses");

        return 0;
    }

    protected function canMigrate(Usr $business, Usr $user)
    {
        global $Connection;
        $userAccounts = $this->getUserAccounts($user, true);
        $accountsToMigrate = $this->getUserAccounts($business, true);
        $this->output->writeln("existing accounts: " . count($userAccounts) . ", to migrate: " . count($accountsToMigrate));

        if (!$this->ignoreAccountLimit && (count($userAccounts) + count($accountsToMigrate)) > PERSONAL_INTERFACE_MAX_ACCOUNTS) {
            $this->output->writeln("too much accounts for personal interface, skipping");

            return false;
        }

        return true;
    }

    protected function migrateUser(Usr $business, Usr $user)
    {
        $this->output->writeln("migrating");
        $this->em->getConnection()->beginTransaction();
        $accounts = $this->getUserAccounts($business);

        foreach ($accounts as $row) {
            if ($row['TableName'] == 'Account') {
                $account = $this->accounts->find($row['ID']);
                $this->migrateAccount($business, $user, $account);
            } else {
                $this->output->writeln("coupon {$row['ID']}");
                $coupon = $this->coupons->find($row['ID']);
                $this->migrateCoupon($business, $user, $coupon);
            }
        }
        $this->em->flush();
        $this->em->getConnection()->commit();
        $this->output->writeln("deleting");
        $this->schemaManager->DeleteRow("Usr", $business->getUserid(), true);
    }

    protected function getUserAccounts(Usr $user, $ownerOnly = false)
    {
        $result = $this->accountListManager
            ->getAccountList(
                $this->optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_INDEXED_BY_HID, true)
            )
            ->getAccounts();

        if ($ownerOnly) {
            $result = array_filter(
                $result,
                function ($row) use ($user) {
                    return $row['TableName'] == 'Account' && $row['UserID'] == $user->getUserid();
                }
            );
        }

        return $result;
    }

    protected function migrateCoupon(Usr $business, Usr $user, Providercoupon $coupon)
    {
        switch ($coupon->getUserid()->getUserid()) {
            case $business->getUserid():
                // business account, will migrate
                if ($coupon->getUseragentid() !== null) {
                    $userAgent = $this->findFamilyMember($user, $coupon->getUseragentid());
                } else {
                    $userAgent = null;
                    $this->output->writeln("no agent");
                }
                $this->output->writeln("migrating coupon {$coupon->getProvidercouponid()}");
                $coupon->setUserid($user);
                $coupon->setUseragentid($userAgent);
                $this->em->persist($coupon);

                break;

            case $user->getUserid():
                $this->output->writeln("admin coupon {$coupon->getProvidercouponid()}, skip");

                break;

            default:
                $this->output->writeln("other user account {$coupon->getProvidercouponid()}");
                $link = $this->createLink($user, $business, $coupon->getUserid());
                $this->shareCoupon($coupon, $link);
        }
    }

    protected function migrateAccount(Usr $business, Usr $user, Account $account)
    {
        switch ($account->getUserid()->getUserid()) {
            case $business->getUserid():
                // business account, will migrate
                if ($account->getUseragentid() !== null) {
                    $userAgent = $this->findFamilyMember($user, $account->getUseragentid());
                } else {
                    $userAgent = null;
                    $this->output->writeln("no agent");
                }
                $this->output->writeln("migrating account {$account->getAccountid()}");
                $this->updateItineraries($account, $user);
                $account->setUserid($user);
                $account->setUseragentid($userAgent);
                $this->em->persist($account);

                break;

            case $user->getUserid():
                $this->output->writeln("admin account {$account->getAccountid()}, skip");

                break;

            default:
                $this->output->writeln("other user account {$account->getAccountid()}");
                $link = $this->createLink($user, $business, $account->getUserid());
                $this->shareAccount($account, $link);
        }
    }

    protected function findFamilyMember(Usr $user, Useragent $agent)
    {
        $this->output->writeln("looking for agent {$agent->getFirstname()} {$agent->getLastname()}");
        $result = $this->agents->findOneBy(['agentid' => $user, 'firstname' => $agent->getFirstname(), 'lastname' => $agent->getLastname()]);

        if (empty($result)) {
            $this->output->writeln("creating");
            $result = new Useragent();
            $result->setAgentid($user);
            $result->setFirstname($agent->getFirstname());
            $result->setLastname($agent->getLastname());
            $result->setAccesslevel($agent->getAccesslevel());
            $result->setSendemails($agent->getSendemails());
            $result->setEmail($agent->getEmail());
            $this->em->persist($result);
            $this->em->flush();
        } else {
            $this->output->writeln("found");
        }

        return $result;
    }

    protected function createLink(Usr $user, Usr $business, Usr $accountUser)
    {
        /** @var Useragent $existingLink */
        $existingLink = $this->agents->findOneBy(['agentid' => $business, 'clientid' => $accountUser]);

        if (empty($existingLink)) {
            throw new \Exception("no link found between {$business->getUserid()} and {$accountUser->getUserid()}");
        }
        /** @var Useragent $newLink */
        $newLink = $this->agents->findOneBy(['agentid' => $user, 'clientid' => $accountUser]);

        if (empty($newLink)) {
            $this->output->writeln("creating link between {$user->getUserid()} and {$accountUser->getUserid()}, existing: {$existingLink->getUseragentid()}, new level: {$existingLink->getAccesslevel()}");
            $newLink = new Useragent();
            $newLink->setAgentid($user);
            $newLink->setClientid($accountUser);
            $newLink->setAccesslevel($existingLink->getAccesslevel());
            $newLink->setEmail($existingLink->getEmail());
            $newLink->setSharebydefault($existingLink->getSharebydefault());
            $newLink->setIsapproved($existingLink->getIsapproved());
            $newLink->setTripsharebydefault($existingLink->getTripsharebydefault());
            $this->em->persist($newLink);
            $this->em->flush();
        }

        if ($newLink->getAccesslevel() != $existingLink->getAccesslevel()) {
            $this->output->writeln("link already exists");
            $this->output->writeln("correcting access level from {$newLink->getAccesslevel()} to {$existingLink->getAccesslevel()}");
            $newLink->setAccesslevel($existingLink->getAccesslevel());
            $this->em->persist($newLink);
        }

        return $newLink;
    }

    protected function shareAccount(Account $account, Useragent $agent)
    {
        $this->output->writeln("sharing account {$account->getAccountid()}");
        $share = $this->accountShare->findOneBy(['accountid' => $account, 'useragentid' => $agent]);

        if (empty($share)) {
            $this->output->writeln("creating share");
            $share = new Accountshare();
            $share->setAccountid($account);
            $share->setUseragentid($agent);
            $this->em->persist($share);
        }
    }

    protected function shareCoupon(Providercoupon $coupon, Useragent $agent)
    {
        $this->output->writeln("sharing coupon {$coupon->getProvidercouponid()}");
        $share = $this->couponShare->findOneBy(['providercouponid' => $coupon, 'useragentid' => $agent]);

        if (empty($share)) {
            $this->output->writeln("creating share");
            $share = new Providercouponshare();
            $share->setProvidercouponid($coupon);
            $share->setUseragentid($agent);
            $this->em->persist($share);
        }
    }

    protected function updateItineraries(Account $account, Usr $user)
    {
        foreach (['Trip', 'Reservation', 'Rental', 'Restaurant', 'Parking'] as $table) {
            $rows = $this->connection->executeUpdate("update {$table} set UserID = ? where AccountID = ?", [$user->getUserid(), $account->getAccountid()]);

            if ($rows > 0) {
                $this->output->writeln("migrated {$rows} {$table}s for account {$account->getAccountid()}");
            }
        }
    }
}
