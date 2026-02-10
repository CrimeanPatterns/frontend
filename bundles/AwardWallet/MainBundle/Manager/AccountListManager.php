<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\AccountList;
use AwardWallet\MainBundle\Globals\AccountList\Loader\DataLoader;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MapperContext;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountListManager
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Counter
     */
    private $counter;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LocalizeService
     */
    private $localizer;

    /**
     * @var DateTimeIntervalFormatter
     */
    private $intervalFormatter;

    /**
     * @var OptionsFactory
     */
    private $optionsFactory;
    /**
     * @var DataLoader
     */
    private $accountListDataLoader;

    public function __construct(
        EntityManagerInterface $em,
        Connection $conn,
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        Counter $counter,
        LocalizeService $localizer,
        DateTimeIntervalFormatter $intervalFormatter,
        OptionsFactory $accountListOptionsFactory,
        DataLoader $accountListDataLoader
    ) {
        $this->connection = $conn;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->counter = $counter;
        $this->localizer = $localizer;
        $this->intervalFormatter = $intervalFormatter;
        $this->optionsFactory = $accountListOptionsFactory;
        $this->accountListDataLoader = $accountListDataLoader;
    }

    public function getUserInfo(Usr $user): array
    {
        $ret = [];
        $ret['ID'] = $user->getUserid();
        $ret['name'] = $user->getFullName();
        $ret['awPlus'] = ($user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) || ($user->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS);
        $ret['disabledExtension'] = false;
        $ret['forceExtensionV3'] = $user->hasRole('ROLE_STAFF_EXTENSION_V3_TESTER');

        return $ret;
    }

    public function getBusinessUserInfo(Usr $user): array
    {
        $ret = $this->getUserInfo($this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user));
        $ret['awPlus'] = true;

        return $ret;
    }

    /**
     * {
     *      ID: identifier,
     *      order: normal sort order,
     *      name: translated Kind.Name,
     *      items: trans key for points format, example "1 Mile", "100 Miles", etc
     * }.
     *
     * @return array
     */
    public function getProviderKindsInfo()
    {
        $providerKinds = [];
        $order = 1;

        foreach (Provider::getKinds() as $id => $name) {
            $providerKinds[] = [
                'ID' => $id,
                'order' => $order++,
                'name' => $this->translator->trans(/** @Ignore */ $name),
                'items' => $name . '.items',
            ];
        }

        return $providerKinds;
    }

    /**
     * {
     *      ID: identifier,
     *      kind: reference to Kind.ID,
     *      name: Provider.DisplayName
     * }.
     *
     * @return array
     */
    public function getProvidersInfo()
    {
        $providers = [];
        $data = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->getSupportedProviders();

        foreach ($data as $provider) {
            $providers[] = [
                'ID' => $provider['providerid'],
                'kind' => $provider['kindid'],
                'name' => $provider['displayname'],
            ];
        }

        return $providers;
    }

    /**
     * {
     *      ID: identifier,
     *      order: normal sort order,
     *      name: UserAgent.Client.FullName or UserAgent.FullName,
     *      owner: boolean, is agent can be new owner of account
     *      hidden: boolean, is agent shown in left menu
     * }.
     *
     * @return array
     */
    public function getAgentsInfo(Usr $providedBusinessUser)
    {
        $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $agentList = $this->counter->getDetailsCountAccountsByUser($this->tokenStorage->getToken()->getUser(), true, false);
        $possibleOwners = $uaRep->getPossibleAccountOwners($providedBusinessUser->getUserid());
        $possibleOwners = array_map(function ($owner) {
            return $owner['UserAgentID'];
        }, $possibleOwners);

        $agents = [];
        $order = 0;

        foreach ($agentList as $agent) {
            $agents[] = [
                'ID' => (isset($agent['UserID'])) ? 'my' : (int) $agent['UserAgentID'],
                'order' => $order++,
                'name' => $this->sanitizeString($agent['UserName']),
                'owner' => (isset($agent['UserID'])) ? true : in_array($agent['UserAgentID'], $possibleOwners),
                'shareable' => false,
                'shareableNotice' => '',
                'hidden' => false,
            ];
        }

        if ($businessUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($providedBusinessUser)) {
            $agents[] = [
                'ID' => $uaRep->findOneBy(['agentid' => $providedBusinessUser, 'clientid' => $businessUser])->getUseragentid(),
                'order' => $order++,
                'name' => $this->sanitizeString($businessUser->getFullName()),
                'owner' => true,
                'shareable' => false,
                'shareableNotice' => '',
                'hidden' => true,
            ];
        }

        $agentList = $uaRep->getShareableAgentsByUserID($providedBusinessUser->getUserid(), 'A', $this->translator);

        foreach ($agentList as $agent) {
            $existsAgent = array_filter($agents, function ($a) use ($agent) {
                return intval($agent['value']) == $a['ID'];
            });

            if (count($existsAgent)) {
                $key = key($existsAgent);
                $agents[$key]['shareable'] = true;
                $agents[$key]['shareableNotice'] = $agent['notice'];
            } else {
                $agents[] = [
                    'ID' => intval($agent['value']),
                    'order' => $order++,
                    'name' => $this->sanitizeString($agent['label']),
                    'owner' => false,
                    'shareable' => true,
                    'shareableNotice' => $agent['notice'],
                    'hidden' => true,
                ];
            }
        }

        return $agents;
    }

    /**
     * {
     *      ID: identifier,
     *      order: auto, normal sort order,
     *      name: UserAgent.Client.FullName or UserAgent.FullName,
     *      owner: false, is agent can be new owner of account
     *      hidden: false, is agent shown in left menu
     * }.
     */
    public function getBusinessAgents(Usr $user): array
    {
        $rep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $members = $rep->getBusinessMembersInfo($user);

        $agents = [];
        $agents[] = [
            'ID' => 'my',
            'name' => $user->getFullName(),
        ];

        foreach ($members as $agent) {
            $agents[] = [
                'ID' => (int) $agent['UserAgentID'],
                'name' => $agent['Name'],
            ];
        }

        return $agents;
    }

    public function getAccount(Options $options, $id, $appendFilters = true)
    {
        $newOptions = clone $options;

        if ($appendFilters) {
            $filter = $newOptions->get(Options::OPTION_FILTER);
        } else {
            $filter = '';
        }

        $filter .= ' AND a.AccountID = ' . $this->em->getConnection()->quote($id, \PDO::PARAM_INT);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($id);

        if (empty($account)) {
            return null;
        }

        if ($account->getUserid()->getUserid() == $newOptions->get(Options::OPTION_USER)->getUserid()) {
            if (!empty($account->getUseragentid())) {
                $newOptions->set(Options::OPTION_USERAGENT, $account->getUseragentid()->getUseragentid());
            } else {
                $newOptions->set(Options::OPTION_USERAGENT, 0);
            }
        } else {
            $agent = $this->em->getConnection()->executeQuery("select ua.UserAgentID from UserAgent ua
			join AccountShare sh on ua.UserAgentID = sh.UserAgentID
			where sh.AccountID = ? and ua.AgentID = ?", [$id, $newOptions->get(Options::OPTION_USER)->getUserid()])->fetchColumn();

            if (empty($agent)) {
                return null;
            }

            $newOptions->set(Options::OPTION_USERAGENT, $agent);
        }

        $newOptions->set(Options::OPTION_FILTER, $filter);
        $newOptions->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1');
        $newOptions->set(Options::OPTION_LOAD_MILE_VALUE, true);

        $accountList = $this->getAccountList($newOptions);

        if (!isset($accountList['a' . $id])) {
            return null;
        }

        return $accountList['a' . $id];
    }

    public function getCoupon(Options $options, $id)
    {
        $newOptions = clone $options;
        $sql = "
            SELECT
                COALESCE(t.UserAgentID, a.UserAgentID, 0) AS UserAgentID
            FROM   ProviderCoupon a
                   LEFT OUTER JOIN
                          ( SELECT sh.ProviderCouponID,
                                  ua.*
                          FROM    ProviderCouponShare sh
                                  JOIN UserAgent ua
                                  ON      sh.UserAgentID    = ua.UserAgentID
                                          AND ua.AgentID    = :user
                                          AND ua.IsApproved = 1
                          WHERE   sh.ProviderCouponID = :acc
                          )
                          t
                   ON     t.ProviderCouponID = a.ProviderCouponID
            WHERE  a.ProviderCouponID  = :acc
            LIMIT 1
        ";
        $statement = $this->em->getConnection()->prepare($sql);
        $userId = $options->get(Options::OPTION_USER)->getUserid();
        $statement->bindParam(':user', $userId, \PDO::PARAM_INT);
        $statement->bindParam(':acc', $id, \PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $filter = $options->get(Options::OPTION_COUPON_FILTER);
        $filter .= ' AND c.ProviderCouponID = ' . $this->em->getConnection()->quote($id, \PDO::PARAM_INT);
        $newOptions
            ->set(Options::OPTION_COUPON_FILTER, $filter)
            ->set(Options::OPTION_FILTER, ' AND 0 = 1')
            ->set(Options::OPTION_USERAGENT, $row['UserAgentID']);

        $accountList = $this->getAccountList($options);

        if (!isset($accountList['c' . $id])) {
            return null;
        }

        return $accountList['c' . $id];
    }

    public function getAccountList(Options $providedOptions): AccountList
    {
        $providedOptions = $this->optionsFactory->createHighlyOpinionatedAndHardcodedOptions($providedOptions);
        $loaderContext = $this->accountListDataLoader->load($providedOptions);
        $mapperContext = new MapperContext(
            $loaderContext,
            $providedOptions
        );

        $formatter = $providedOptions->get(Options::OPTION_FORMATTER);

        if ($formatter) {
            $formatter->alterTemplate($mapperContext);
        }

        $list = new AccountList(
            $mapperContext,
            $formatter
        );

        return $list;
    }

    private function sanitizeString($string)
    {
        return htmlspecialchars_decode($string);
    }
}
