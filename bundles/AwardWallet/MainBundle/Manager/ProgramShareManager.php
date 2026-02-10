<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\AbAccountProgram;
use AwardWallet\MainBundle\Entity\AbCustomProgram;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\Exception\BookerRequiredException;
use AwardWallet\MainBundle\Manager\Exception\EmptyPasswordException;
use AwardWallet\MainBundle\Manager\Exception\LocallyStoredPasswordException;
use AwardWallet\MainBundle\Manager\Exception\ProgramManagerRequiredException;
use AwardWallet\MainBundle\Manager\Exception\ProgramOwnerRequiredException;
use AwardWallet\MainBundle\Manager\Exception\RequestOwnerRequiredException;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Invalidator;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

class ProgramShareManager
{
    /** @var EntityManager */
    protected $em;

    /** @var AwTokenStorageInterface */
    protected $tokenStorage;

    /** @var Mailer */
    protected $mailer;

    protected $router;

    protected ManagerRegistry $doctrine;

    /** @var Usr */
    protected $user;

    protected $environment;
    /**
     * @var LocalPasswordsManager
     */
    private $localPasswordsManager;
    /**
     * @var Invalidator
     */
    private $cacheInvalidator;
    /**
     * @var CacheManager
     */
    private $cacheManager;
    /**
     * @var AccountManager
     */
    private $accountManager;
    /**
     * @var LogoManager
     */
    private $logoManager;
    /**
     * @var string
     */
    private $secret;
    /**
     * @var AccountListManager
     */
    private $accountListManager;
    /**
     * @var OptionsFactory
     */
    private $optionsFactory;

    public function __construct(
        EntityManager $em,
        AwTokenStorageInterface $tokenStorage,
        Mailer $mailer,
        RouterInterface $router,
        ManagerRegistry $doctrine,
        $env,
        LocalPasswordsManager $localPasswordsManager,
        Invalidator $cacheInvalidator,
        CacheManager $cacheManager,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        AccountManager $accountManager,
        LogoManager $logoManager,
        $secret
    ) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->tokenStorage = $tokenStorage;
        $this->environment = $env;
        $this->router = $router;
        $this->doctrine = $doctrine;

        if (!empty($tokenStorage->getToken())) {
            $this->user = $tokenStorage->getToken()->getUser();
        }

        $this->localPasswordsManager = $localPasswordsManager;
        $this->cacheInvalidator = $cacheInvalidator;
        $this->cacheManager = $cacheManager;
        $this->accountManager = $accountManager;
        $this->logoManager = $logoManager;
        $this->secret = $secret;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    public function setUser(Usr $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        if (!$this->user) {
            $this->setUser($this->tokenStorage->getBusinessUser());
        }

        return $this->user;
    }

    /**
     * @return UserAgent
     * @throws Exception\BookerRequiredException
     */
    public function connectToBooker(Usr $user, Usr $booker)
    {
        if (!$booker->isBooker()) {
            throw new BookerRequiredException();
        }

        $agent = $this->getConnectToBooker($user, $booker);

        if (!$agent) {
            $agent = new Useragent();
            $agent->setAgentid($booker);
            $agent->setClientid($user);
            $agent->setSharebydefault(false);
            $agent->setSendemails(true);
            $agent->setTripsharebydefault(1);
            $this->em->persist($agent);
        }

        if ($agent->getAccesslevel() < UseragentRepository::ACCESS_WRITE || $agent->getAccesslevel() == UseragentRepository::ACCESS_NONE) {
            $agent->setAccesslevel(UseragentRepository::ACCESS_WRITE);
        }
        $agent->setIsapproved(1);

        $backlink = $this->getBacklinkToBooker($user, $booker);

        if (!$backlink) {
            $backlink = new Useragent();
            $backlink->setAgentid($user);
            $backlink->setClientid($booker);
            $backlink->setSharebydefault(false);
            $backlink->setSendemails(false);
            $backlink->setTripsharebydefault(0);
            $backlink->setAccesslevel(UseragentRepository::ACCESS_NONE);
            $this->em->persist($backlink);
        }
        $backlink->setIsapproved(1);

        $this->em->getRepository(\AwardWallet\MainBundle\Entity\Travelplanshare::class)->shareUserPlans($user, $agent);

        $this->em->flush();

        return $agent;
    }

    public function disconnectUserFromBooker(Usr $user, Usr $booker)
    {
        $u = $this->getUser();
        $this->setUser($user);
        $this->disconnectFromBooker($booker);
        $this->setUser($u);
    }

    /**
     * @throws Exception\BookerRequiredException
     */
    public function disconnectFromBooker(Usr $booker)
    {
        if (!$booker->isBooker()) {
            throw new BookerRequiredException();
        }

        $agent = $this->getConnectToBooker($this->user, $booker);

        if ($agent) {
            $this->em->remove($agent);
        }
        $backlink = $this->getBacklinkToBooker($this->user, $booker);

        if ($backlink) {
            $this->em->remove($backlink);
        }

        if ($agent) {
            $db = $this->doctrine->getConnection();
            $db->executeQuery("DELETE FROM `AccountShare` WHERE `UserAgentID`=? ", [$agent->getUseragentid()]);
            $this->em->flush();
        }
    }

    /**
     * @return bool on success share
     * @throws Exception\ProgramManagerRequiredException
     * @throws Exception\ProgramOwnerRequiredException
     */
    public function shareProgram(AbAccountProgram $program)
    {
        $this->testAccessToProgram($program);
        $request = $program->getRequest();
        $account = $program->getAccount();
        $booker = $request->getBooker();

        if ($account->getUserid()->getUserid() != $this->user->getUserid()) {
            $this->testAccessToOtherUserAccounts($account->getUserid());
            $agent = $this->connectToBooker($account->getUserid(), $booker);
        } else {
            $agent = $this->connectToBooker($this->user, $booker);
        }
        $accountShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Accountshare::class);
        $accountShare = $accountShareRep->findOneBy(['accountid' => $account, 'useragentid' => $agent]);

        if ($accountShare) {
            return false;
        }
        $accountShareRep->addAccountShare($account, $agent);

        return true;
    }

    /**
     * @throws Exception\ProgramManagerRequiredException
     * @throws Exception\ProgramOwnerRequiredException
     */
    public function removeProgramShare(AbAccountProgram $program)
    {
        $this->testAccessToProgram($program);
        $request = $program->getRequest();
        $account = $program->getAccount();

        if ($account->getUserid()->getUserid() != $this->user->getUserid()) {
            $this->testAccessToOtherUserAccounts($account->getUserid());
        }
        $booker = $request->getBooker();

        if ($this->isConnectedToBooker($booker)) {
            $agent = $this->getConnectToBooker($this->user, $booker);
            $accountShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Accountshare::class);
            $accountShare = $accountShareRep->findOneBy(['accountid' => $account, 'useragentid' => $agent]);

            if ($accountShare) {
                $this->em->remove($accountShare);
                $this->em->flush();
            }
        }
    }

    /**
     * @throws Exception\RequestOwnerRequiredException
     */
    public function removeAllProgramsShare(AbRequest $request)
    {
        $this->testAccessToRequest($request);
        $booker = $request->getBooker();
        $accountShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Accountshare::class);
        $agent = $this->getConnectToBooker($this->getUser(), $booker, 0);

        // todo evil fat code
        foreach ($request->getAccounts() as $program) {
            $account = $program->getAccount();
            $accountShare = $accountShareRep->findOneBy(['accountid' => $account, 'useragentid' => $agent]);

            if ($accountShare) {
                $this->em->remove($accountShare);
            }
        }
        $this->em->flush();
    }

    /**
     * @param AbAccountProgram[]|AbCustomProgram[] $programs
     */
    public function requestShareForRequest(AbRequest $request, $programs)
    {
        // todo
    }

    public function requestShareForProgram(AbAccountProgram $program)
    {
        $this->testAccessToProgram($program);

        if (!$program->getRequested()) {
            $program->setRequested(true);
            $this->em->flush();
        }
    }

    public function requestShareForCustomProgram(AbCustomProgram $program)
    {
        $this->testAccessToCustomProgram($program);

        if (!$program->getRequested()) {
            $program->setRequested(true);
            $this->em->flush();
        }
    }

    /**
     * @param int $min_level
     * @return bool
     */
    public function isConnectedToBooker(Usr $booker, $min_level = UseragentRepository::ACCESS_WRITE)
    {
        $agent = $this->getConnectToBooker($this->user, $booker);

        if (!$agent) {
            return false;
        }

        if (!$agent->getIsapproved()) {
            return false;
        }

        // todo member level?
        if ($agent->getAccesslevel() < $min_level) {
            return false;
        }

        return true;
    }

    /**
     * @return UserAgent
     * @throws Exception\BookerRequiredException
     */
    public function getConnectToBooker(Usr $user, Usr $booker)
    {
        if (!$booker->isBooker()) {
            throw new BookerRequiredException();
        }
        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $agent = $userAgentRep->findOneBy(['agentid' => $booker, 'clientid' => $user]);

        return $agent;
    }

    /**
     * @return UserAgent
     * @throws Exception\BookerRequiredException
     */
    public function getBacklinkToBooker(Usr $user, Usr $booker)
    {
        if (!$booker->isBooker()) {
            throw new BookerRequiredException();
        }
        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $backlink = $userAgentRep->findOneBy(['clientid' => $booker, 'agentid' => $user]);

        return $backlink;
    }

    public function convertCustomToProgram(AbCustomProgram $program, Account $account)
    {
        $this->testAccessToCustomProgram($program);
        $this->testAccessToAccount($account);
        // todo need test providerId ???
        $request = $program->getRequest();
        $accountProgramRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbAccountProgram::class);
        $accountProgram = $accountProgramRep->findOneBy(['AccountID' => $account, 'RequestID' => $request]);

        if (!$accountProgram) {
            $accountProgram = new AbAccountProgram();
            $accountProgram->setRequest($request);
            $accountProgram->setAccount($account);
            $this->em->persist($accountProgram);
        }
        $accountProgram->setRequested($program->getRequested());
        $this->em->flush();

        foreach ($program->getRequest()->getMessages() as $message) {
            if ($message->isShareRequest()) {
                if (false !== $key = array_search($program->getAbCustomProgramID(), $message->getMetadata()->getCPR())) {
                    $meta = clone $message->getMetadata();
                    $cpr = $message->getMetadata()->getCPR();
                    unset($cpr[$key]);
                    $meta->setCPR($cpr);
                    $apr = $meta->getAPR();
                    $apr[] = $accountProgram->getAbAccountProgramID();
                    $meta->setAPR($apr);
                    $message->setMetadata($meta);
                }
            }
        }

        $this->em->remove($program);
        $this->em->flush();

        return $accountProgram;
    }

    /**
     * @return string
     * @throws Exception\LocallyStoredPasswordException
     * @throws Exception\EmptyPasswordException
     */
    public function revealPassword(Account $account)
    {
        $this->testAccessToAccount($account);

        if ($account->getSavepassword() == SAVE_PASSWORD_LOCALLY) {
            if ($this->user != $account->getUserid()) {
                throw new LocallyStoredPasswordException();
            } else {
                if ($this->localPasswordsManager->hasPassword($account->getAccountid())) {
                    return $this->localPasswordsManager->getPassword($account->getAccountid());
                } else {
                    throw new EmptyPasswordException();
                }
            }
        } else {
            return $account->getPass();
        }
    }

    /* ============================================================================== */

    /**
     * @throws Exception\RequestOwnerRequiredException
     */
    public function testAccessToRequest(AbRequest $request)
    {
        if ($this->user->getUserid() != $request->getUser()->getUserid()) {
            throw new RequestOwnerRequiredException();
        }
    }

    /**
     * @throws Exception\ProgramOwnerRequiredException
     */
    public function testAccessToProgram(AbAccountProgram $program)
    {
        if ($this->user->getUserid() != $program->getRequest()->getUser()->getUserid()) {
            throw new ProgramOwnerRequiredException();
        }
    }

    /**
     * @throws Exception\ProgramOwnerRequiredException
     */
    public function testAccessToCustomProgram(AbCustomProgram $program)
    {
        if ($this->user->getUserid() != $program->getRequest()->getUser()->getUserid()) {
            throw new ProgramOwnerRequiredException();
        }
    }

    /**
     * @throws Exception\ProgramManagerRequiredException
     */
    public function testAccessToOtherUserAccounts(Usr $user)
    {
        if ($this->user == $user) {
            return;
        }
        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $shared = $userAgentRep->findOneBy(['agentid' => $this->user, 'clientid' => $user]);

        if (!($shared && $shared->getIsapproved() && in_array($shared->getAccesslevel(), [UseragentRepository::ACCESS_WRITE, UseragentRepository::ACCESS_ADMIN, UseragentRepository::ACCESS_BOOKING_MANAGER, UseragentRepository::ACCESS_BOOKING_REFERRAL]))) {
            throw new ProgramManagerRequiredException();
        }
    }

    public function testAccessToAccount(Account $account)
    {
        if ($this->user != $account->getUserid()) {
            $this->testAccessToOtherUserAccounts($account->getUserid());
        }
    }

    /**
     * @param bool $isNeedToshareAccounts
     * @param int $accessLevel
     * @return Useragent
     */
    public function apiSharingConfirm(Usr $user, $isNeedToshareAccounts, $accessLevel)
    {
        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $timelineShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);

        [$connection, $linkFrom] = $userAgentRep->inviteUser($user, $this->tokenStorage->getBusinessUser(), true);
        /** @var UserAgent $connection */
        $connection->setAccesslevel($accessLevel);
        $connection->setPopupShown(true);
        $connection->setTripAccessLevel($accessLevel == ACCESS_WRITE ? 1 : 0);

        $timelineShareRep->addTimelineShare($connection);

        foreach ($this->getUser()->getFamilyMembers() as $familyMember) {
            $timelineShareRep->addTimelineShare($connection, $familyMember);
        }
        $this->em->flush();

        if ($isNeedToshareAccounts) {
            $this->shareAccounts($connection);
        }

        $this->cacheInvalidator->connectionsChanged($user->getUserid(), false);
        $this->cacheManager->invalidateTags(Tags::getAllAccountsCounterTags($this->getUser()->getUserid()), false);

        return $connection;
    }

    public function apiSharingShareAll(Usr $user, Useragent $userAgent, $type, ?Options $additionalAccountListOptions = null)
    {
        $userId = $user->getUserid();
        $timelineShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);

        if ($userAgent->isFamilyMember()) {
            throw new \Exception("Family members not allowed here");
        }

        if ($userAgent->getClientid()->getUserid() != $user->getUserid()) {
            throw new \Exception("Users mismatch, not your connection?");
        }
        $agent = $userId == $userAgent->getAgentid()->getUserid() ? $userAgent->getClientid() : $userAgent->getAgentid();

        if ($agent->isBusiness() && $agent->isBooker()) {
            $this->logoManager->setBooker($agent);
        }

        $userAgent->setSharebydefault(true);
        $userAgent->setTripAccessLevel($type == 'full' ? 1 : 0);
        $userAgent->setTripsharebydefault(true);
        $userAgent->setAccesslevel($type == 'full' ? UseragentRepository::ACCESS_WRITE : UseragentRepository::ACCESS_READ_ALL);

        $timelineShareRep->addTimelineShare($userAgent);

        foreach ($user->getFamilyMembers() as $familyMember) {
            $timelineShareRep->addTimelineShare($userAgent, $familyMember);
        }

        $this->em->flush();

        $usrAccounts = $this->shareAccounts($userAgent, $additionalAccountListOptions);

        $this->cacheInvalidator->connectionsChanged($agent->getUserid(), false);
        $this->cacheManager->invalidateTags(Tags::getAllAccountsCounterTags($userId), false);

        return [
            $agent,
            $userAgent,
            $usrAccounts,
            // TODO: uncomment for PHP 7.1
            // 'agent' => $agent,
            // 'accounts' => $usrAccounts
        ];
    }

    public function apiSharingDenyAll(Usr $user, Useragent $userAgent)
    {
        $userId = $user->getUserid();
        $agent = $userAgent->getClientid();

        if ($agent->isBusiness() && $agent->isBooker()) {
            $this->logoManager->setBooker($agent);
        }

        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $connection = null;

        if ($userId == $userAgent->getAgentid()->getUserid()) {
            $connection = $userAgentRep->findOneBy(['agentid' => $agent, 'clientid' => $this->getUser()]);
        } elseif ($userId == $userAgent->getClientid()->getUserid()) {
            $connection = $userAgent;
        }

        if ($connection) {
            $connection->setAccesslevel(ACCESS_READ_NUMBER);
            $connection->setSendemails(false);
            $connection->setTripAccessLevel(0);
            $connection->setSharebydefault(false);
            $connection->setTripsharebydefault(false);

            $db = $this->em->getConnection();
            $db->executeQuery("DELETE FROM `AccountShare` WHERE `UserAgentID`=? ", [$connection->getUseragentid()]);
            $db->executeQuery("DELETE FROM `TimelineShare` WHERE `UserAgentID`=? ", [$connection->getUseragentid()]);
            $db->executeQuery("DELETE FROM `ProviderCouponShare` WHERE `UserAgentID`=? ", [$connection->getUseragentid()]);
        }

        $this->em->flush();

        $this->cacheInvalidator->connectionsChanged($agent->getUserid(), false);
        $this->cacheManager->invalidateTags(Tags::getAllAccountsCounterTags($userId), false);
    }

    /**
     * @return array
     */
    public function getSharedAccounts(Usr $booker)
    {
        $conn = $this->em->getConnection();
        $sql = '
            SELECT accsh.AccountID
            FROM `AccountShare` accsh
            JOIN `UserAgent` ua
                    ON accsh.UserAgentID = ua.UserAgentID
            WHERE
                    ua.AgentID  = :agentId
                AND ua.ClientID = :clientId
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('agentId', $booker->getUserid(), \PDO::PARAM_INT);
        $stmt->bindValue('clientId', $this->getUser()->getUserid(), \PDO::PARAM_INT);
        $stmt->execute();
        $accounts = $stmt->fetchAll();

        return empty($accounts) ? [] : array_column($accounts, 'AccountID');
    }

    /**
     * @param Collection $accounts
     * @return bool
     */
    public function bindAccounts($accounts, Usr $booker)
    {
        if (!($this->getUser() instanceof Usr)) {
            return false;
        }

        $existAccounts = $this->getSharedAccounts($booker);
        $shareAccounts = [];

        foreach ($accounts as $program) {
            $shareAccounts[] = $program->getAccount()->getId();

            if (SAVE_PASSWORD_LOCALLY === $program->getAccount()->getSavepassword()) {
                $this->accountManager->setAccountStorage($program->getAccount(), SAVE_PASSWORD_LOCALLY, SAVE_PASSWORD_DATABASE);
            }
        }

        $insertAccounts = array_values(array_diff($shareAccounts, $existAccounts));
        $removeAccounts = array_values(array_diff($existAccounts, $shareAccounts));

        $db = $this->em->getConnection();
        $agent = $booker->findUserAgent($this->getUser()->getUserid());

        if (!$agent) {
            return false;
        }

        $userAgentId = $agent->getUseragentid();

        for ($i = -1, $iCount = count($insertAccounts); ++$i < $iCount;) {
            $db->executeQuery('INSERT INTO `AccountShare` (AccountID, UserAgentID) VALUES(?, ?)', [$insertAccounts[$i], $userAgentId], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
        }

        for ($i = -1, $iCount = count($removeAccounts); ++$i < $iCount;) {
            $db->executeQuery('DELETE FROM `AccountShare` WHERE AccountID = ? AND UserAgentID = ?', [$removeAccounts[$i], $userAgentId], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
        }

        return true;
    }

    public function getShareAllCode(Useragent $userAgent, $accessType)
    {
        return base64_encode($userAgent->getUseragentid() . '#' . $accessType . '#' . sha1($this->secret . $userAgent->getUseragentid() . $accessType . $userAgent->getAgentid()->getCreationdatetime()->format("c")));
    }

    /**
     * returns [$userAgentObject, 'full'] or null.
     *
     * @return array|null
     */
    public function decodeShareAllCode($code)
    {
        $params = explode('#', base64_decode($code));

        if (count($params) != 3) {
            return null;
        }

        [$userAgentId, $accessType, $hash] = $params;

        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $userAgent = $userAgentRep->find($userAgentId);

        if (empty($userAgent)) {
            return null;
        }

        $validCode = $this->getShareAllCode($userAgent, $accessType);

        if ($validCode != $code) {
            return null;
        }

        return [$userAgent, $accessType];
    }

    /**
     * @return array
     */
    private function shareAccounts(Useragent $connection, ?Options $additionalAccountListOptions = null)
    {
        $userId = $this->getUser()->getUserid();
        $accountListOptions = $this->optionsFactory->createDefaultOptions();

        if ($additionalAccountListOptions) {
            $accountListOptions->updateFrom($additionalAccountListOptions);
        }

        $usrAccounts = $this->accountListManager
            ->getAccountList(
                $accountListOptions
                    ->set(Options::OPTION_USER, $this->getUser())
                    ->set(Options::OPTION_INDEXED_BY_HID, true)
            )
            ->getAccounts();

        $accountShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Accountshare::class);
        $couponShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercouponshare::class);

        $accountsToShare = $couponsToShare = [];

        foreach ($usrAccounts as $id => &$usrAccount) {
            if ($usrAccount['TableName'] == 'Account') {
                if ($usrAccount['UserID'] != $userId) {
                    unset($usrAccounts[$id]);

                    continue;
                }
                $accountsToShare[] = $usrAccount['ID'];
            } else {
                if ($usrAccount['UserID'] != $userId) {
                    unset($usrAccounts[$id]);

                    continue;
                }
                $couponsToShare[] = $usrAccount['ID'];
            }
        }

        $accountShareRep->shareAccounts($accountsToShare, $connection);
        $couponShareRep->shareCoupons($couponsToShare, $connection);

        $this->accountManager->storeLocalPasswords($this->getUser());

        return $usrAccounts;
    }
}
