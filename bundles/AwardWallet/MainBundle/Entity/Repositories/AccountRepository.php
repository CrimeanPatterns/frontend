<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Providercouponshare;
use AwardWallet\MainBundle\Entity\Transaction;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\UserOwnedInterface;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\Builder;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function trim;

class AccountRepository extends EntityRepository
{
    private $detailsCountCache = [];
    /**
     * @var OwnerRepository
     */
    private $ownerRepository;

    public function setOwnerRepository(OwnerRepository $ownerRepository)
    {
        $this->ownerRepository = $ownerRepository;
    }

    public function getCountAccountsByUser($userID, $enabled = true)
    {
        $connection = $this->getEntityManager()->getConnection();
        $user = $this->getEntityManager()->getRepository(Usr::class)->find($userID);
        $providerFilter = $user->getProviderFilter();
        $stateFilter = $enabled ? 'AND a.State > ' . ACCOUNT_DISABLED : '';
        $statement = $connection->prepare("
			SELECT COUNT(*) AS Cnt 
			FROM   Account a 
			       LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
			WHERE  a.UserID = :userID 
			       AND $providerFilter
			       $stateFilter
						   
			UNION ALL
			
			SELECT COUNT(*) AS Cnt 
			FROM   ProviderCoupon 
			WHERE  UserID = :userID
			
			UNION ALL
			
			SELECT COUNT(ash.AccountShareID) AS Cnt 
			FROM
				AccountShare ash
				LEFT JOIN Account a	ON ash.AccountID = a.AccountID
				LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
				LEFT JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID
				LEFT OUTER JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
			WHERE
				ua.AgentID = :userID
				AND ua.IsApproved = 1
				AND ( au.IsApproved = 1
				OR au.IsApproved IS NULL )
				AND $providerFilter
				$stateFilter

            UNION ALL

			SELECT COUNT(pcsh.ProviderCouponShareID) AS Cnt
			FROM
				ProviderCouponShare pcsh
				LEFT JOIN ProviderCoupon pc ON pcsh.ProviderCouponID = pc.ProviderCouponID
				JOIN UserAgent ua on pcsh.UserAgentID = ua.UserAgentID
				LEFT OUTER JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
			WHERE
				ua.AgentID = :userID
				AND ua.IsApproved = 1
				AND ( au.IsApproved = 1	OR au.IsApproved IS NULL )
			");
        $statement->bindParam(':userID', $userID, \PDO::PARAM_INT);
        $statement->execute();
        $accounts = $statement->fetchAll();
        $count = 0;

        foreach ($accounts as $account) {
            $count += $account['Cnt'];
        }

        return $count;
    }

    public function getCountAccountsByUserAgent($userID, $clientID, $enabled = true)
    {
        if (empty($clientID)) {
            return 0;
        }
        $connection = $this->getEntityManager()->getConnection();
        $user = $this->getEntityManager()->getRepository(Usr::class)->find($userID);
        $providerFilter = $user->getProviderFilter();
        $stateFilter = $enabled ? 'AND a.State > ' . ACCOUNT_DISABLED : '';
        $statement = $connection->prepare("
			SELECT COUNT(ash.AccountShareID) AS Cnt
			FROM
				AccountShare ash
				LEFT JOIN Account a	ON ash.AccountID = a.AccountID
				LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
				LEFT JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID
				LEFT OUTER JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
			WHERE
				ua.AgentID = :userID
				AND ua.ClientID = :clientID
				AND ua.IsApproved = 1
				AND ( au.IsApproved = 1
				OR au.IsApproved IS NULL )
				AND $providerFilter
				$stateFilter

            UNION ALL

			SELECT COUNT(pcsh.ProviderCouponShareID) AS Cnt
			FROM
				ProviderCouponShare pcsh
				LEFT JOIN ProviderCoupon pc ON pcsh.ProviderCouponID = pc.ProviderCouponID
				JOIN UserAgent ua on pcsh.UserAgentID = ua.UserAgentID
				LEFT OUTER JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
			WHERE
				ua.AgentID = :userID
				AND ua.ClientID = :clientID
				AND ua.IsApproved = 1
				AND ( au.IsApproved = 1	OR au.IsApproved IS NULL )
			");
        $statement->bindParam(':userID', $userID, \PDO::PARAM_INT);
        $statement->bindParam(':clientID', $clientID, \PDO::PARAM_INT);
        $statement->execute();
        $accounts = $statement->fetchAll();
        $count = 0;

        foreach ($accounts as $account) {
            $count += $account['Cnt'];
        }

        return $count;
    }

    /**
     * Incorrect work!!!
     * use version from Counter class, this version does not use cache
     * TODO: remove, use \AwardWallet\MainBundle\Service\Counter::getDetailsCountAccountsByUser.
     *
     * @param int|Usr $userID
     * @return array
     */
    public function getDetailsCountAccountsByUser($userID)
    {
        if ($userID instanceof Usr) {
            $usr = $userID;
            $userID = $usr->getUserid();
        }

        if (isset($this->detailsCountCache[$userID])) {
            return $this->detailsCountCache[$userID];
        }

        $connection = $this->getEntityManager()->getConnection();
        $userAgentRep = $this->getEntityManager()->getRepository(Useragent::class);
        $userRep = $this->getEntityManager()->getRepository(Usr::class);
        $contacts = $userAgentRep->getOtherUsers($userID);
        $all = [
            'UserName' => 'All',
            'UserAgentID' => null,
            'Count' => $this->getCountAccountsByUser($userID),
        ];
        $otherCount = 0;
        $user = $this->getEntityManager()->getRepository(Usr::class)->find($userID);
        $filter = $user->getProviderFilter();
        $uas = [];
        $fm = [];
        $cm = [];

        foreach ($contacts as $k => $contact) {
            if ($contact['ClientID'] != '') {
                $cm[] = $contact['UserAgentID'];
            } else {
                $fm[] = $contact['UserAgentID'];
            }
            $uas[$contact['UserAgentID']] = $k;
            $contacts[$k]['Count'] = 0;
        }

        if (sizeof($cm)) {
            $stmt = $connection->executeQuery(
                "
                SELECT ash.UserAgentID, COUNT(ash.AccountShareID) AS Cnt
                FROM   AccountShare ash
                       LEFT JOIN Account a
                       ON     ash.AccountID = a.AccountID
                       LEFT JOIN Provider p
                       ON     a.ProviderID = p.ProviderID
                WHERE  ash.UserAgentID     IN (?)
                       AND $filter
                GROUP BY ash.UserAgentID
                ",
                [$cm],
                [Connection::PARAM_INT_ARRAY]
            );

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (isset($uas[$row['UserAgentID']]) && isset($contacts[$uas[$row['UserAgentID']]])) {
                    $contacts[$uas[$row['UserAgentID']]]['Count'] = $row['Cnt'];
                    $otherCount += $row['Cnt'];
                }
            }
        }

        if (sizeof($fm)) {
            $stmt = $connection->executeQuery(
                "
                SELECT a.UserAgentID, COUNT(a.AccountID) AS Cnt
                FROM   Account a
                       LEFT JOIN Provider p
                       ON     a.ProviderID = p.ProviderID
                WHERE  a.UserAgentID       IN (?)
                       AND $filter
                GROUP BY a.UserAgentID
                ",
                [$fm],
                [Connection::PARAM_INT_ARRAY]
            );

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (isset($uas[$row['UserAgentID']]) && isset($contacts[$uas[$row['UserAgentID']]])) {
                    $contacts[$uas[$row['UserAgentID']]]['Count'] = $row['Cnt'];
                    $otherCount += $row['Cnt'];
                }
            }
        }

        if (!isset($usr)) {
            $usr = $this->getEntityManager()->getRepository(Usr::class)->find($userID);
        }

        array_unshift($contacts, [
            'FirstName' => $usr->getFirstname(),
            'LastName' => $usr->getLastname(),
            'UserName' => $usr->getFullName(),
            'UserID' => $userID,
            'UserAgentID' => null,
            'ClientID' => null,
            'AccountLevel' => $usr->getAccountlevel(),
            'Company' => $usr->getCompany(),
            'Count' => $all['Count'] - $otherCount,
            'AccessLevel' => ACCESS_WRITE,
        ]);
        array_unshift($contacts, $all);

        $this->detailsCountCache[$userID] = $contacts;

        return $contacts;
    }

    public function getAccountsArrayByUser(
        $user,
        $otherFilter = '',
        $couponFilter = '',
        $userAgentAccountFilter = null,
        $userAgentCouponFilter = null,
        $wantCheck = false,
        $wantEdit = false,
        $stateFilter = 'a.state > 0'
    ) {
        $userRep = $this->getEntityManager()->getRepository(Usr::class);

        if (!($user instanceof Usr)) {
            $user = $userRep->find($user);
        }

        $otherFilter = strtolower($otherFilter);
        $stateFilter = strtolower($stateFilter);

        $userAccountQuery = $this->getEntityManager()->createQueryBuilder();
        $userAccountQuery->select(['a', 'p'])
            ->from(Account::class, 'a')
            ->leftJoin('a.providerid', 'p', 'WITH')
            ->andWhere('a.user = :userID')
            ->andWhere($userRep->getUserAndProviderFilterDQL($userAccountQuery, $user))
            ->setParameter('userID', $user)
            ->setMaxResults(300);

        if ($otherFilter) {
            $userAccountQuery->andWhere($otherFilter);
        }

        if ($stateFilter) {
            $userAccountQuery->andWhere($stateFilter);
        }

        $shareAccountQuery = $this->getEntityManager()->createQueryBuilder();
        $shareAccountQuery->select(['a', 'p', 'u', 'aua', 'ua.accesslevel', 'ua.useragentid'])
            ->from(Account::class, 'a')
            ->leftJoin('a.providerid', 'p', 'WITH')
            ->leftJoin('a.user', 'u', 'WITH')
            ->leftJoin('a.useragentid', 'aua', 'WITH')
            ->leftJoin(Accountshare::class, 'ash', 'WITH', 'ash.accountid = a.accountid')
            ->leftJoin(Useragent::class, 'ua', 'WITH', 'ash.useragentid = ua.useragentid')
            ->leftJoin(Useragent::class, 'au', 'WITH', 'au.clientid = ua.agentid and au.agentid = ua.clientid')
            ->andWhere('ua.agentid = :userID')
            ->andWhere('ua.isapproved = 1')
            ->andWhere('au.isapproved = 1 OR au.isapproved IS NULL')
            ->andWhere($userRep->getUserAndProviderFilterDQL($shareAccountQuery, $user))
            ->setParameter('userID', $user)
            ->setMaxResults(300);

        if ($otherFilter) {
            $shareAccountQuery->andWhere($otherFilter);
        }

        if ($stateFilter) {
            $shareAccountQuery->andWhere($stateFilter);
        }

        $ret = array_merge(
            $this->accountsToArrayFields($userAccountQuery->getQuery()->getResult()),
            $this->accountsToArrayFields($shareAccountQuery->getQuery()->getResult())
        );

        $couponFilter = strtolower($couponFilter);

        $userCouponQuery = $this->getEntityManager()->createQueryBuilder();
        $userCouponQuery->select(['pc'])
            ->from(Providercoupon::class, 'pc')
            ->andWhere('pc.user = :userID')
            ->setParameter('userID', $user)
            ->setMaxResults(300);

        if ($couponFilter) {
            $userCouponQuery->andWhere($couponFilter);
        }

        $shareCouponQuery = $this->getEntityManager()->createQueryBuilder();
        $shareCouponQuery->select(['pc', 'u', 'pcua', 'ua.accesslevel', 'ua.useragentid'])
            ->from(Providercoupon::class, 'pc')
            ->leftJoin('pc.user', 'u', 'WITH')
            ->leftJoin('pc.useragentid', 'pcua', 'WITH')
            ->leftJoin(Providercouponshare::class, 'pcsh', 'WITH', 'pcsh.providercouponid = pc.providercouponid')
            ->leftJoin(Useragent::class, 'ua', 'WITH', 'pcsh.useragentid = ua.useragentid')
            ->leftJoin(Useragent::class, 'au', 'WITH', 'au.clientid = ua.agentid and au.agentid = ua.clientid')
            ->andWhere('ua.agentid = :userID')
            ->andWhere('ua.isapproved = 1')
            ->andWhere('au.isapproved = 1 OR au.isapproved IS NULL')
            ->setParameter('userID', $user)
            ->setMaxResults(300);

        if ($couponFilter) {
            $shareCouponQuery->andWhere($couponFilter);
        }

        $ret = array_merge(
            $ret,
            $this->couponsToArrayFields($userCouponQuery->getQuery()->getResult()),
            $this->couponsToArrayFields($shareCouponQuery->getQuery()->getResult())
        );

        return $ret;
    }

    /**
     * @param Account[]|array $accounts
     * @return array
     */
    public function accountsToArrayFields($accounts)
    {
        $ret = [];

        foreach ($accounts as $account) {
            $fields = $this->getEmptyFields();
            $data = [];

            if (!($account instanceof Account)) {
                $data = $account;
                $account = null;

                foreach ($data as $k => $m) {
                    if ($m instanceof Account) {
                        $account = $m;
                        unset($data[$k]);
                    }
                }

                if (!$account) {
                    continue;
                }
            }

            $fields['TableName'] = 'Account';
            $fields['ID'] = $account->getAccountid();
            $fields['Login'] = htmlentities($account->getLogin());
            $fields['Login2'] = htmlentities($account->getLogin2());
            $fields['Login3'] = htmlentities($account->getLogin3());
            $fields['ErrorCode'] = $account->getErrorcode();
            $fields['ErrorMessage'] = $account->getErrormessage();
            $fields['State'] = $account->getState();
            $fields['Pass'] = $account->getPass();

            $fields['ExpirationDate'] = $account->getExpirationdate() ? $account->getExpirationdate()->format('Y-m-d H:i:s') : null;
            $fields['LastChangeDate'] = $account->getLastchangedate() ? $account->getLastchangedate()->format('Y-m-d H:i:s') : null;
            $fields['ModifyDate'] = $account->getModifydate() ? $account->getModifydate()->format('Y-m-d H:i:s') : null;
            $fields['PassChangeDate'] = $account->getPasschangedate() ? $account->getPasschangedate()->format('Y-m-d H:i:s') : null;
            $fields['SuccessCheckDate'] = $account->getSuccesscheckdate() ? $account->getSuccesscheckdate()->format('Y-m-d H:i:s') : null;
            $fields['LastCheckItDate'] = $account->getLastcheckitdate() ? $account->getLastcheckitdate()->format('Y-m-d H:i:s') : null;
            $fields['LastCheckHistoryDate'] = $account->getLastcheckhistorydate() ? $account->getLastcheckhistorydate()->format('Y-m-d H:i:s') : null;
            $fields['QueueDate'] = $account->getQueuedate() ? $account->getQueuedate()->format('Y-m-d H:i:s') : null;
            $fields['RawUpdateDate'] = $account->getUpdatedate() ? $account->getUpdatedate()->format('Y-m-d H:i:s') : null;
            $fields['UpdateDate'] = $account->getUpdatedate() ? $account->getUpdatedate()->format('F j, Y') : null; // sql '%M %e, %Y'

            $fields['ChangeCount'] = $account->getChangecount();
            $fields['ExpirationAutoSet'] = $account->getExpirationautoset();
            $fields['SavePassword'] = $account->getSavepassword();
            $fields['ErrorCount'] = $account->getErrorcount();
            $fields['Goal'] = $account->getGoal();
            $fields['ExpirationWarning'] = $account->getExpirationwarning();
            $fields['SubAccounts'] = $account->getSubaccounts();
            $fields['Question'] = $account->getQuestion();
            $fields['DontTrackExpiration'] = $account->getDonttrackexpiration();
            $fields['NotRelated'] = $account->getNotrelated();
            $fields['LastDurationWithoutPlans'] = $account->getLastdurationwithoutplans();
            $fields['LastDurationWithPlans'] = $account->getLastdurationwithplans();
            $fields['IsActiveTab'] = $account->getIsactivetab();
            $fields['comment'] = $account->getComment();
            $fields['RawBalance'] = $account->getBalance();
            $fields['ChangesConfirmed'] = $account->isChangesConfirmed();
            $fields['Balance'] = !\is_null($account->getBalance()) ? rtrim(rtrim(sprintf('%0.10f', $account->getBalance()), '0'), '.') : null;
            $fields['TotalBalance'] = !\is_null($account->getTotalbalance()) ? rtrim(rtrim(sprintf('%0.10f', $account->getTotalbalance()), '0'), '.') : null;
            $fields['LastBalance'] = !\is_null($account->getLastbalance()) ? rtrim(rtrim(sprintf('%0.7f', $account->getLastbalance()), '0'), '.') : null;

            if (!empty($currency = $account->getCurrency())) {
                $fields['ManualCurrencyCode'] = $currency->getCode();
                $fields['ManualCurrencySign'] = !empty($currency->getSign()) ? $currency->getSign() : $currency->getName();
            } else {
                $fields['ManualCurrencyCode'] = null;
                $fields['ManualCurrencySign'] = null;
            }

            if ($account->getProviderid()) {
                $provider = $account->getProviderid();
                $fields['ProviderCode'] = $provider->getCode();
                $fields['ProviderName'] = $provider->getShortname();
                $fields['FullProviderName'] = $provider->getName();
                $fields['LoginURL'] = $provider->getLoginurl();
                $fields['ProgramName'] = $provider->getProgramname();
                $fields['DisplayName'] = $provider->getDisplayname();
                $fields['Kind'] = $provider->getKind();
                $fields['ProviderID'] = $provider->getProviderid();
                $fields['AutoLogin'] = $provider->getAutologin() != AUTOLOGIN_DISABLED;
                $fields['CanCheck'] = $provider->getCancheck();
                $fields['CanReceiveEmail'] = $provider->getCanReceiveEmail();
                $fields['CanCheckBalance'] = $provider->getCancheckbalance();
                $fields['Site'] = $provider->getSite();
                $fields['TradeMin'] = $provider->getTrademin();
                $fields['ExpirationDateNote'] = $provider->getExpirationdatenote();
                $fields['BalanceFormat'] = $provider->getBalanceformat();
                $fields['AllowFloat'] = $provider->getAllowfloat();
                $fields['FAQ'] = $provider->getFaq();
                $fields['CanCheckExpiration'] = $provider->getCancheckexpiration();
                $fields['CanCheckItinerary'] = $provider->getCancheckitinerary();
                $fields['ExpirationAlwaysKnown'] = $provider->getExpirationalwaysknown();
                $fields['AAADiscount'] = $provider->getAaadiscount();
                $fields['CustomDisplayName'] = $provider->getCustomdisplayname();
                $fields['BarCode'] = $provider->getBarcode();
                $fields['IATACode'] = $provider->getIATACode();
                $fields['EliteLevelsCount'] = $provider->getElitelevelscount();
                $fields['Currency'] = $provider->getCurrency()->getCurrencyid();
                $fields['ProviderGroup'] = $provider->getProvidergroup();
                $fields['Code'] = $provider->getCode();
                $fields['DeepLinking'] = $provider->getDeeplinking();
                $fields['AvgDurationWithoutPlans'] = $provider->getAvgdurationwithoutplans();
                $fields['CanSavePassword'] = $provider->getCanSavePassword();
                $fields['MobileAutoLogin'] = $provider->getMobileautologin();
                $fields['ExpirationUnknownNote'] = $provider->getExpirationunknownnote();
                $fields['ProviderEngine'] = $provider->getEngine();
                $fields['ProviderState'] = $provider->getState();
                $fields['CheckInBrowser'] = $provider->getCheckinbrowser();

                if ($provider->getAllianceid()) {
                    $fields['AllianceID'] = $provider->getAllianceid()->getAllianceid();
                    $fields['AllianceAlias'] = $provider->getAllianceid()->getAlias();
                }
            } else {
                $fields['ProviderName'] = $account->getProgramname();
                $fields['LoginURL'] = $account->getLoginurl();
                $fields['ProgramName'] = $account->getProgramname();
                $fields['DisplayName'] = $account->getProgramname();
                $fields['Kind'] = $account->getKind() ?: 1;
                $fields['CheckInBrowser'] = $account->isCheckInBrowser() ?: 0;
            }

            $fields['UserID'] = $account->getUserid()->getUserid();
            $fields['AccountLevel'] = $account->getUserid()->getAccountlevel();
            $fields['UserPictureVer'] = $account->getUserid()->getPicturever();
            $fields['UserPictureExt'] = $account->getUserid()->getPictureext();
            $fields['AutoGatherPlans'] = (int) $account->getUserid()->getAutogatherplans();

            if ($account->getUseragentid()) {
                $fields['UserAgentID'] = $account->getUseragentid()->getUseragentid();
                $fields['UserName'] = $account->getUseragentid()->getFullName();
                $fields['AgentComment'] = $account->getUseragentid()->getComment();
                $fields['UserAgentPictureVer'] = $account->getUseragentid()->getPicturever();
                $fields['UserAgentPictureExt'] = $account->getUseragentid()->getPictureext();
            } else {
                $fields['UserName'] = $account->getUserid()->getFullName();
            }

            if (array_key_exists('accesslevel', $data)) {
                $fields['AccessLevel'] = $data['accesslevel'];
            }

            if (array_key_exists('useragentid', $data)) {
                $fields['ShareUserAgentID'] = $data['useragentid'];
            }

            $ret[] = $fields;
        }

        return $ret;
    }

    /**
     * @param ProviderCoupon[]|array $coupons
     * @return array
     */
    public function couponsToArrayFields($coupons)
    {
        $ret = [];

        foreach ($coupons as $coupon) {
            $fields = $this->getEmptyFields();
            $data = [];

            if (!($coupon instanceof ProviderCoupon)) {
                $data = $coupon;
                $coupon = null;

                foreach ($data as $k => $m) {
                    if ($m instanceof ProviderCoupon) {
                        $coupon = $m;
                        unset($data[$k]);
                    }
                }

                if (!$coupon) {
                    continue;
                }
            }

            $fields['TableName'] = 'Coupon';
            $fields['ID'] = $coupon->getProvidercouponid();
            $fields['Description'] = htmlentities($coupon->getDescription());
            $fields['Value'] = htmlentities($coupon->getValue());
            $fields['ProgramName'] = htmlentities($coupon->getProgramname());
            $fields['DisplayName'] = htmlentities($coupon->getProgramname());
            $fields['ProviderName'] = htmlentities($coupon->getProgramname());
            $fields['FullProviderName'] = htmlentities($coupon->getProgramname());
            $fields['ExpirationDate'] = $coupon->getExpirationdate() ? $coupon->getExpirationdate()->format('Y-m-d H:i:s') : null;
            $fields['Kind'] = $coupon->getKind();
            $fields['ExpirationAutoSet'] = EXPIRATION_UNKNOWN;
            $fields['Balance'] = 0;
            $fields['TotalBalance'] = 0;
            $fields['DeepLinking'] = 0;
            $fields['AutoLogin'] = 0;
            $fields['CanCheck'] = 0;
            $fields['CanReceiveEmail'] = 0;
            $fields['AllianceID'] = 0;
            $fields['DeepLinking'] = 0;
            $fields['CanCheckBalance'] = 0;
            $fields['CanCheckExpiration'] = 0;
            $fields['CanCheckItinerary'] = 0;
            $fields['ExpirationAlwaysKnown'] = 0;
            $fields['RawBalance'] = 0;
            $fields['AAADiscount'] = 0;
            $fields['SubAccounts'] = 0;
            $fields['CustomDisplayName'] = 0;

            if (!empty($currency = $coupon->getCurrency())) {
                $fields['ManualCurrencyCode'] = $currency->getCode();
                $fields['ManualCurrencySign'] = !empty($currency->getSign()) ? $currency->getSign() : $currency->getName();
            } else {
                $fields['ManualCurrencyCode'] = null;
                $fields['ManualCurrencySign'] = null;
            }

            $fields['UserID'] = $coupon->getUserid()->getUserid();
            $fields['AccountLevel'] = $coupon->getUserid()->getAccountlevel();
            $fields['UserPictureVer'] = $coupon->getUserid()->getPicturever();
            $fields['UserPictureExt'] = $coupon->getUserid()->getPictureext();
            $fields['AutoGatherPlans'] = (int) $coupon->getUserid()->getAutogatherplans();

            if ($coupon->getUseragentid()) {
                $fields['UserAgentID'] = $coupon->getUseragentid()->getUseragentid();
                $fields['UserName'] = $coupon->getUseragentid()->getFullName();
                $fields['AgentComment'] = $coupon->getUseragentid()->getComment();
                $fields['UserAgentPictureVer'] = $coupon->getUseragentid()->getPicturever();
                $fields['UserAgentPictureExt'] = $coupon->getUseragentid()->getPictureext();
            } else {
                $fields['UserName'] = $coupon->getUserid()->getFullName();
            }

            if (array_key_exists('accesslevel', $data)) {
                $fields['AccessLevel'] = $data['accesslevel'];
            }

            if (array_key_exists('useragentid', $data)) {
                $fields['ShareUserAgentID'] = $data['useragentid'];
            }

            $ret[] = $fields;
        }

        return $ret;
    }

    /**
     * please use getAccountsSQLByUserAgent, this version is slow on business.
     */
    public function getAccountsSQLByUser(
        $userID,
        $otherFilter = '',
        $couponFilter = '',
        $userAgentAccountFilter = null,
        $userAgentCouponFilter = null,
        $wantCheck = false,
        $wantEdit = false,
        $stateFilter = 'a.State > 0',
        $found_rows = false,
        $accountFields = null,
        $couponFields = null
    ) {
        $connection = $this->getEntityManager()->getConnection();

        if (!isset($userAgentAccountFilter) || empty($userAgentAccountFilter) || !isset($userAgentCouponFilter)) {
            $userAgentRep = $this->getEntityManager()->getRepository(Useragent::class);
            $userAgentRep->setAgentFilters($userID, UseragentRepository::ALL_USERAGENTS, $wantCheck, $wantEdit);
            $userAgentAccountFilter = $userAgentRep->userAgentAccountFilter;
            $userAgentCouponFilter = $userAgentRep->userAgentCouponFilter;
        }
        $user = $this->getEntityManager()->getRepository(Usr::class)->find($userID);
        $otherFilter .= ' AND ' . $user->getProviderFilter();

        $sql = '
			SELECT ' . ($found_rows ? 'SQL_CALC_FOUND_ROWS' : '') . '
			       ' . (empty($accountFields) ? $this->accountFields() : $accountFields) . '
			FROM   Account a USE INDEX (idx_Account_UserID, idx_Account_UserAgentID)
			       LEFT OUTER JOIN Provider p
			       ON     a.ProviderID = p.ProviderID
			       LEFT OUTER JOIN Alliance al
			       ON     p.AllianceID = al.AllianceID
			       LEFT OUTER JOIN UserAgent ua
			       ON     a.UserAgentID = ua.UserAgentID
			       LEFT OUTER JOIN Currency curr ON a.CurrencyID = curr.CurrencyID
			       LEFT OUTER JOIN Currency currProvider ON p.Currency = currProvider.CurrencyID
			       LEFT OUTER JOIN
			              ( SELECT AccountShare.UserAgentID,
			                      AccountID                ,
			                      AgentID                  ,
			                      AccessLevel
			              FROM    UserAgent,
			                      AccountShare
			              WHERE   UserAgent.UserAgentID = AccountShare.UserAgentID
			                      AND UserAgent.AgentID = ' . $connection->quote($userID, \PDO::PARAM_INT) . "
			              )
			              ash
			       ON     a.AccountID = ash.AccountID
			              /*joins*/
			              ,
			              Usr u
			WHERE  a.UserID = u.UserID
				   AND $stateFilter
			       AND
			       (
			              $userAgentAccountFilter
			       )
			       $otherFilter
		";

        if (isset($userAgentCouponFilter) && $userAgentCouponFilter != '' && isset($couponFilter)) {
            $sql .= '
				UNION

				SELECT
					' . (empty($couponFields) ? $this->couponFields() : $couponFields) . '
				FROM   ProviderCoupon c
				       LEFT OUTER JOIN UserAgent ua ON c.UserAgentID = ua.UserAgentID
				       LEFT OUTER JOIN Currency curr ON c.CurrencyID = curr.CurrencyID
				       LEFT OUTER JOIN ProviderCouponType ct ON c.TypeID = ct.TypeID 
				       LEFT OUTER JOIN
				              ( SELECT csh.UserAgentID    ,
				                      csh.ProviderCouponID,
				                      ua.AgentID          ,
				                      ua.AccessLevel
				              FROM    UserAgent ua,
				                      ProviderCouponShare csh
				              WHERE   ua.UserAgentID = csh.UserAgentID
				                      AND ua.AgentID = ' . $connection->quote($userID, \PDO::PARAM_INT) . "
				              )
				              ash
				       ON     c.ProviderCouponID = ash.ProviderCouponID,
				              Usr u
				WHERE  c.UserID = u.UserID
				       AND
				       (
				              $userAgentCouponFilter
				       )
				       $couponFilter
			";
        }

        return $sql;
    }

    public function getAccountsSQLByUserAgent(
        $userID,
        $otherFilter = '',
        $couponFilter = '',
        $userAgent = null,
        $stateFilter = 'a.State > 0',
        $found_rows = false,
        $joins = '',
        $extraAccountFields = '',
        $extraCouponFields = '',
        $providerFilter = null
    ) {
        $connection = $this->getEntityManager()->getConnection();
        $user = $this->getEntityManager()->getRepository(Usr::class)->find($userID);

        if (!isset($providerFilter)) {
            $providerFilter = $user->getProviderFilter();
        }

        $otherFilter .= ' AND ' . $providerFilter;

        if ($userAgent > 0) {
            $agent = $this->getEntityManager()->getRepository(Useragent::class)->find($userAgent);
        }
        $sql = [];

        if (empty($userAgent) || (isset($agent) && empty($agent->getClientid()))) { // my accounts
            $sql[] = '
				SELECT
					   ' . $this->accountFields('null', 'null') . " $extraAccountFields
				FROM   Account a
					   JOIN Usr u ON a.UserID = u.UserID
					   LEFT OUTER JOIN Provider p ON     a.ProviderID = p.ProviderID
					   LEFT OUTER JOIN Alliance al ON     p.AllianceID = al.AllianceID
					   LEFT OUTER JOIN UserAgent ua ON     a.UserAgentID = ua.UserAgentID
					   $joins
					   LEFT OUTER JOIN Currency curr ON a.CurrencyID = curr.CurrencyID
					   LEFT OUTER JOIN Currency currProvider ON p.Currency = currProvider.CurrencyID
				WHERE
					   a.UserID = $userID
					   " . (!empty($userAgent) ? ' AND a.UserAgentID = ' . $connection->quote(
                $userAgent,
                \PDO::PARAM_INT
            ) : '') . "
					   AND $stateFilter
					   " . \str_replace(
                '[RawBalance]',
                SQL_ACCOUNT_RAW_BALANCE,
                \str_replace(
                    '[DisplayName]',
                    SQL_ACCOUNT_DISPLAY_NAME,
                    \str_replace(
                        '[UserAgentID]',
                        'a.UserAgentID',
                        \str_replace(
                            '[AllianceID]',
                            'p.AllianceID',
                            \str_replace('[ShareUserAgentID]', 'null', $otherFilter)
                        )
                    )
                )
            ) . '
			';
        }

        if (empty($userAgent) || (isset($agent) && !empty($agent->getClientid()))) { // accounts shared with me
            $sql[] = '
				SELECT
					   ' . $this->accountFields('ua.AccessLevel', 'ash.UserAgentID', 'fm') . " $extraAccountFields
				FROM   Account a
					   JOIN Usr u ON a.UserID = u.UserID
					   JOIN AccountShare ash ON a.AccountID = ash.AccountID
					   JOIN UserAgent ua ON ash.UserAgentID = ua.UserAgentID
					   JOIN UserAgent au ON au.AgentID = ua.ClientID AND ua.AgentID = au.ClientID AND au.IsApproved = 1
					   LEFT OUTER JOIN Provider p ON     a.ProviderID = p.ProviderID
					   LEFT OUTER JOIN Alliance al ON     p.AllianceID = al.AllianceID
					   LEFT OUTER JOIN UserAgent fm ON a.UserAgentID = fm.UserAgentID
					   $joins
					   LEFT OUTER JOIN Currency curr ON a.CurrencyID = curr.CurrencyID
					   LEFT OUTER JOIN Currency currProvider ON p.Currency = currProvider.CurrencyID
				WHERE
					   ua.AgentID = " . $connection->quote($userID, \PDO::PARAM_INT) . '
					   ' . (!empty($userAgent) ? ' AND ash.UserAgentID = ' . $connection->quote(
                $userAgent,
                \PDO::PARAM_INT
            ) : '') . "
					   AND $stateFilter
					   " . \str_replace(
                '[RawBalance]',
                SQL_ACCOUNT_RAW_BALANCE,
                \str_replace(
                    '[DisplayName]',
                    SQL_ACCOUNT_DISPLAY_NAME,
                    \str_replace(
                        '[UserAgentID]',
                        'a.UserAgentID',
                        \str_replace(
                            '[AllianceID]',
                            'p.AllianceID',
                            \str_replace('[ShareUserAgentID]', 'ash.UserAgentID', $otherFilter)
                        )
                    )
                )
            ) . '
			';
        }

        if (isset($couponFilter)) {
            if (empty($userAgent) || (isset($agent) && empty($agent->getClientid()))) { // my coupons
                $sql[] = '
					SELECT
						   ' . $this->couponFields('null', 'null') . " $extraCouponFields
					FROM   ProviderCoupon c
						   JOIN Usr u on c.UserID = u.UserID
						   LEFT OUTER JOIN UserAgent ua ON c.UserAgentID = ua.UserAgentID
						   LEFT OUTER JOIN Currency curr ON c.CurrencyID = curr.CurrencyID
						   LEFT OUTER JOIN ProviderCouponType ct ON c.TypeID = ct.TypeID
					WHERE  c.UserID = $userID
						   " . (!empty($userAgent) ? ' AND c.UserAgentID = ' . $connection->quote(
                    $userAgent,
                    \PDO::PARAM_INT
                ) : '') . '
						   ' . \str_replace(
                    '[RawBalance]',
                    '0',
                    \str_replace(
                        '[DisplayName]',
                        SQL_COUPON_DISPLAY_NAME,
                        \str_replace(
                            '[UserAgentID]',
                            'c.UserAgentID',
                            \str_replace(
                                '[AllianceID]',
                                '0',
                                \str_replace('[ShareUserAgentID]', 'null', $couponFilter)
                            )
                        )
                    )
                );
            }

            if (empty($userAgent) || (isset($agent) && !empty($agent->getClientid()))) { // coupons shared with me
                $sql[] = '
					SELECT
						   ' . $this->couponFields('ua.AccessLevel', 'ash.UserAgentID', 'fm') . " $extraCouponFields
					FROM   ProviderCoupon c
						   JOIN Usr u ON c.UserID = u.UserID
						   JOIN ProviderCouponShare ash ON c.ProviderCouponID = ash.ProviderCouponID
						   JOIN UserAgent ua ON ash.UserAgentID = ua.UserAgentID
						   JOIN UserAgent au ON au.AgentID = ua.ClientID AND ua.AgentID = au.ClientID AND au.IsApproved = 1
						   LEFT OUTER JOIN UserAgent fm ON c.UserAgentID = fm.UserAgentID
						   LEFT OUTER JOIN Currency curr ON c.CurrencyID = curr.CurrencyID
						   LEFT OUTER JOIN ProviderCouponType ct ON c.TypeID = ct.TypeID
					WHERE
						   ua.AgentID = " . $connection->quote($userID, \PDO::PARAM_INT) . '
						   ' . (!empty($userAgent) ? ' AND ash.UserAgentID = ' . $connection->quote($userAgent, \PDO::PARAM_INT) : '') . '
						   ' . \str_replace(
                    '[RawBalance]',
                    '0',
                    \str_replace(
                        '[DisplayName]',
                        SQL_COUPON_DISPLAY_NAME,
                        \str_replace(
                            '[UserAgentID]',
                            'c.UserAgentID',
                            \str_replace(
                                '[AllianceID]',
                                '0',
                                \str_replace('[ShareUserAgentID]', 'ash.UserAgentID', $couponFilter)
                            )
                        )
                    )
                );
            }
        }

        $result = implode(' UNION ', $sql);
        $result = \str_replace('[UserName]', SQL_USER_NAME, $result);

        if ($found_rows) {
            $result = \preg_replace('/^\s*SELECT\s/ims', 'SELECT SQL_CALC_FOUND_ROWS ', $result, 1);
        }

        return $result;
    }

    /**
     * return formatted balance in full version.
     */
    public function formatFullBalance($balance, $providerCode, $balanceFormat, $change = false)
    {
        if (\is_null($balance) && !$change) {
            return 'n/a';
        }

        $balance = number_format_localized($balance, 2);
        $localizer = getSymfonyContainer()->get(LocalizeService::class);
        $decimalSep = $localizer->getDecimalPoint();
        $balance = \preg_replace('/\\' . $decimalSep . '00$/ims', '', $balance);

        if ('' != ($trimmed = \trim($balanceFormat)) && 'function' != $trimmed) {
            $balance = \preg_replace(['/\%0?\.2f/ims', '/\%d/ims'], $balance, $balanceFormat);
        }

        $balance = \str_replace($decimalSep . '0 ', ' ', $balance);

        return $balance;
    }

    public function getAccountNumberByAccountID($accountID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = '
        SELECT IF(ap.Val IS NOT NULL, ap.Val, a.Login) AccountNumber         
        FROM Account a
        LEFT JOIN AccountProperty ap ON ap.AccountID = a.AccountID
        JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID 
            AND pp.Kind = ' . PROPERTY_KIND_NUMBER . '
        WHERE a.AccountID = ?
        ';
        $stmt = $connection->executeQuery($sql, [$accountID], [\PDO::PARAM_INT]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        return !empty($res) ? $res['AccountNumber'] : null;
    }

    public function deleteAccount($accountId)
    {
        $em = $this->getEntityManager();
        $transaction = $em->getRepository(Transaction::class)->findByAccountid($accountId);
        $accountShare = $em->getRepository(Accountshare::class)->findByAccountid($accountId);
        /** @var Account $account */
        $account = $em->getRepository(Account::class)->find($accountId);

        foreach ($transaction as $obj) {
            $em->remove($obj);
        }

        foreach ($accountShare as $obj) {
            $em->remove($obj);
        }

        if ($account) {
            if ($account->getState() === ACCOUNT_PENDING) {
                $account->setState(ACCOUNT_IGNORED);
            } elseif ($account->getState() >= ACCOUNT_DISABLED) {
                $em->remove($account);
            }
        }

        $em->flush();
    }

    public function isUniqueAccount(Account $account, &$existAccount = null, &$canEdit = false)
    {
        $connection = $this->getEntityManager()->getConnection();
        $filter = '';
        $login2 = $account->getLogin2();

        if (isset($login2) && $login2 != '') {
            $filter .= ' AND a.Login2 = ' . $connection->quote($login2, \PDO::PARAM_STR);
        }
        $login3 = $account->getLogin3();

        if (isset($login3) && $login3 != '') {
            $filter .= ' AND a.Login3 = ' . $connection->quote($login3, \PDO::PARAM_STR);
        }
        $accountId = $account->getAccountid();

        if (isset($accountId) && is_numeric($accountId)) {
            $filter .= ' AND a.AccountID <> ' . $accountId;
        }

        $provider = $account->getProviderid();
        $providerValue = isset($provider) ? '=' . $connection->quote($provider->getProviderid(), \PDO::PARAM_INT) : 'IS NULL';
        $sql = "
			SELECT a.*         ,
			       ua.FirstName,
			       ua.LastName
			FROM   Account a
			       LEFT OUTER JOIN UserAgent ua
			       ON     a.UserAgentID                                  = ua.UserAgentID
			WHERE  a.ProviderID											 $providerValue
			       AND a.UserID                                          = ?
			       AND a.Login                                           = ?
				   $filter
		";
        // lookup in my accounts
        $stmt = $connection->executeQuery(
            $sql,
            [$account->getUserid()->getUserid(), $account->getLogin()],
            [\PDO::PARAM_INT, \PDO::PARAM_STR]
        );
        $existAccount = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existAccount !== false) {
            $canEdit = true;

            return false;
        }

        return true;
    }

    public function getProgramMessage(array $fields, $dateFormat, TranslatorInterface $translator)
    {
        if (isset($fields['SuccessCheckDateTs'])) {
            $lastSuccessCheckDateDate = date($dateFormat, $fields['SuccessCheckDateTs']);
        }

        if (isset($fields['UpdateDateTs'])) {
            $lastUpdateDate = date($dateFormat, $fields['UpdateDateTs']);
        }
        /** @var array[
         *     string 'Title' title
         *     string 'Desc' description
         *     string 'DateInfo' information about last (success) update date
         * ] $messageData */
        $messageData = [];

        if ('' == $fields['ProviderID']) {
            /** @Ignore */
            /** @Desc("Notice") */
            $messageData['Title'] = $translator->trans('notice');
            /** @Ignore */
            /** @Desc("This is a custom program that you added. Award Wallet cannot automatically check the balance on this program.") */
            $messageData['Description'] = $translator->trans('custom-program.notice');
        } else {
            switch ($fields['ErrorCode']) {
                case ACCOUNT_INVALID_PASSWORD:
                    /** @Ignore */
                    /** @Desc("Invalid logon") */
                    $messageData['Title'] = $translator->trans('error.award.account.invalid-logon.title');

                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        /** @Desc("Last time account information was successfully retrieved on %lastUpdate%") */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;

                case ACCOUNT_WARNING:
                    /** @Ignore */
                    $messageData['Title'] = $translator->trans('notice');

                    if (isset($lastUpdateDate)) {
                        /** @Ignore */
                        /** @Desc("Last time your rewards info was retrieved from the %displayName% web site on: %updateDate%") */
                        $messageData['DateInfo'] = $translator->trans('last-time-rewards-retrieving', ['%displayName%' => $fields['DisplayName'], '%updateDate%' => $lastUpdateDate]);
                    }

                    break;

                case ACCOUNT_UNCHECKED:
                    /** @Ignore */
                    $messageData['Title'] = $translator->trans('notice');
                    /** @Ignore */
                    /** @Desc("Account information has not been retrieved from the %displayName% web site yet. You have to click 'update' in order for us to retrieve the account info.") */
                    $messageData['Description'] = $translator->trans('award.account.info-not-retrieved-yet', ['%displayName%' => $fields['DisplayName']]);

                    break;

                case ACCOUNT_MISSING_PASSWORD:
                    /** @Ignore */
                    /** @Desc("Missing Password") */
                    $messageData['Title'] = $translator->trans('error.award.account.missing-password.title');
                    /** @Ignore */
                    /** @Desc("You opted to save the password for this award program locally, this computer / browser does not have it stored.") */
                    $messageData['Description'] = $translator->trans('error.award.account.missing-password.text');

                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;

                case ACCOUNT_LOCKOUT:
                    /** @Ignore */
                    /** @Desc("Your account is locked out") */
                    $messageData['Title'] = $translator->trans('error.award.account.locked-out.title');

                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;

                case ACCOUNT_PROVIDER_ERROR:
                    /** @Ignore */
                    /** @Desc("Error occurred") */
                    $messageData['Title'] = $translator->trans('error.award.account.other.title');

                    /** @Ignore */
                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;

                case ACCOUNT_ENGINE_ERROR:
                    /** @Ignore */
                    $messageData['Title'] = $translator->trans('error.award.account.other.title');

                    /** @Ignore */
                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;

                case ACCOUNT_PREVENT_LOCKOUT:
                    /** @Ignore */
                    /** @Desc("Username or password is incorrect") */
                    $messageData['Title'] = $translator->trans('error.award.account.invalid-credentials.title');
                    /** @Ignore */
                    /** @Desc("To prevent your account from being locked out by the provider please change the password or the user name you entered on AwardWallet.com as these credentials appear to be invalid.") */
                    $messageData['Description'] = $translator->trans('error.award.account.invalid-credentials.text');

                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;

                case ACCOUNT_QUESTION:
                    /** @Ignore */
                    /** @Desc("Security question") */
                    $messageData['Title'] = $translator->trans('error.award.account.security-question.title');
                    /** @Ignore */
                    /** @Desc("It looks like you are being prompted to answer a security question on the website which holds your account balance. Please click the "Update Account" button to answer this question.") */
                    $messageData['Description'] = $translator->trans('error.award.account.security-question.text');

                    if (isset($lastSuccessCheckDateDate)) {
                        /** @Ignore */
                        $messageData['DateInfo'] = $translator->trans('last-time-account-retrieving', ['%lastUpdate%' => $lastSuccessCheckDateDate]);
                    }

                    break;
            }
        }

        if ('' != $fields['ErrorMessage']) {
            $messageData['ProviderMessage'] = $fields['ErrorMessage'];
        }

        return $messageData;
    }

    //	TODO Deprecated, not used
    public function getUserAccountsByProvider(Provider $provider, Usr $user, ?Useragent $userAgent = null, $business = null)
    {
        $connection = $this->getEntityManager()->getConnection();
        $userId = $user->getUserid();
        $providerId = $provider->getProviderid();

        $qAgent = '
				select ua.UserAgentID, ua.ClientID
				from UserAgent ua
				join Usr c on c.UserID = ua.ClientID
				where ua.IsApproved = 1
					and ua.AgentID = ?
				and ua.TripShareByDefault = 1
					AND ua.AccessLevel in (' . ACCESS_READ_ALL . ', ' . ACCESS_WRITE . ', ' . ACCESS_ADMIN . ', ' . ACCESS_BOOKING_MANAGER . ', ' . ACCESS_BOOKING_VIEW_ONLY . ')';

        $stmt = $connection->executeQuery($qAgent, [$userId], [\PDO::PARAM_INT]);
        $agents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $agentFilters = [];

        foreach ($agents as $agent) {
            $agentFilters[] = "( a.UserID = {$agent['ClientID']}
	and a.AccountID in (
		select ash.AccountID
		from AccountShare ash, Account a
		where ash.AccountID = a.AccountID and a.UserID = {$agent['ClientID']}
		and ash.UserAgentID = {$agent['UserAgentID']}
	) )";
        }

        $filters = "(a.UserID = {$userId})";

        if (count($agentFilters) > 0) {
            $filters .= ' or ' . implode(' or ', $agentFilters);
        }

        //        $qAccounts = "
        //                SELECT a.AccountID AS ID,
        //                       a.Login,
        //                       a.ProviderID,
        //                       p.Code AS ProviderCode,
        //                       coalesce(ua.FirstName, u.FirstName) AS FirstName,
        //                       coalesce(ua.LastName, u.LastName) AS LastName,
        //                       p.DisplayName,
        //                       trim(TRAILING '.'
        //                FROM trim(TRAILING '0'
        //                FROM round(a.Balance, 10))) AS Balance,
        //                     p.CheckInBrowser
        //                FROM Account a
        //                JOIN Usr u ON a.UserID = u.UserID
        //                JOIN Provider p ON a.ProviderID = p.ProviderID
        //                LEFT OUTER JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
        //                WHERE a.ProviderID = ?
        //                  AND a.UserID = ?
        //                  and ($filters)
        //                  AND a.State > 0
        //                ORDER BY FirstName,
        //                         LastName
        //        ";

        $qAccounts = "SELECT a.AccountID as ID, a.Login, a.ProviderID, p.Code as ProviderCode,
coalesce(ua.FirstName, u.FirstName) as FirstName,
coalesce(ua.LastName, u.LastName) as LastName,
p.DisplayName,
trim(trailing '.' from trim(trailing '0' from round(a.Balance, 10))) as Balance,
p.CheckInBrowser, p.CanCheck, a.UserAgentID
FROM Account a
join Usr u on a.UserID = u.UserID
join Provider p on a.ProviderID = p.ProviderID
left outer join UserAgent ua on a.UserAgentID = ua.UserAgentID
WHERE a.ProviderID = {$providerId} and ($filters) and a.State > 0 and a.Disabled = 0
order by FirstName, LastName";

        $stmt = $connection->executeQuery($qAccounts, [$providerId, $userId], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
        $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $qAccountProperties = '
                SELECT pp.Name,
                       pp.Code,
                       ap.Val,
                       pp.Kind,
                       pp.Visible,
                       pp.SortIndex
                FROM AccountProperty ap,
                     ProviderProperty pp
                WHERE ap.ProviderPropertyID = pp.ProviderPropertyID
                  AND ap.AccountID = ?
                  AND ap.SubAccountID IS NULL
                  AND pp.Kind = 1
        ';

        $accountRep = $this->getEntityManager()->getRepository(Account::class);
        $agent = $userAgent ? $userAgent->getAgentid() : null;

        foreach ($accounts as $key => $account) {
            $userAccount = $accountRep->find($account['ID']);

            if (
                $userAgent
                && (
                    ($userAgent->isFamilyMember() && $account['UserAgentID'] != $userAgent->getUseragentid())
                || (!$userAgent->isFamilyMember() && (($agent->isBusiness() && $userAccount->getUseragentid() && $userAccount->getUseragentid()->isFamilyMember()) || !$userAccount->isSharedWith($agent)))
                || ($business && $userAccount->getUserid() != $business && !$userAccount->isSharedWith($business))
                )
            ) {
                unset($accounts[$key]);

                continue;
            }

            $stmt = $connection->executeQuery($qAccountProperties, [$account['ID']], [\PDO::PARAM_INT]);
            $accountProperty = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!empty($accountProperty)) {
                $accounts[$key]['Number'] = $accountProperty['Val'];
            }
        }

        $data = [
            'accounts' => $accounts,
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function validateAccount(Account $account, ValidatorInterface $validator, Builder $builder, DataTransformerInterface $dataTransformer)
    {
        $errors = [];
        $fields = $builder->getFormTemplate(
            $account->getUserid(),
            $account->getProviderid(),
            null
        )->fields;

        foreach ($fields as $field) {
            $methodName = 'get' . ucfirst($field['property']);
            $constraints = $field['options']['constraints'] ?? [];

            if (method_exists($account, $methodName) && sizeof($constraints)) {
                $er = $validator->validate($account->$methodName(), $constraints);

                if (sizeof($er)) {
                    foreach ($er as $_er) {
                        $errors[] = [
                            'id' => $field['id'],
                            'error' => (string) $_er,
                        ];
                    }
                }
            }
        }
        $er = $validator->validate($dataTransformer->transform($account));

        if (sizeof($er)) {
            foreach ($er as $_er) {
                $errors[] = [
                    'id' => $_er->getPropertyPath(),
                    'error' => (string) $_er,
                ];
            }
        }

        return $errors;
    }

    public function getPendingsQuery(Usr $user, bool $order = false): QueryBuilder
    {
        $builder =
            $this->_em->createQueryBuilder()
            ->select('a')
            ->from(Account::class, 'a')
            ->join('a.providerid', 'p')
            ->andWhere('a.user = :userid')->setParameter('userid', $user->getUserid())
            ->andWhere('a.state = :state')->setParameter('state', ACCOUNT_PENDING)
            ->andWhere($user->getProviderFilter('p.state'));

        if ($order) {
            $builder
                ->addSelect("
                    (case
                        when p.code = 'delta' then 1
                        when p.code = 'rapidrewards' then 1
                        when p.code = 'mileageplus' then 1
                        else 0
                    end) as HIDDEN pessimizedProvider"
                )
                ->orderBy('pessimizedProvider', 'asc')
                ->addOrderBy('p.accounts', 'DESC');
        }

        return $builder;
    }

    public function count(array $criteria)
    {
        return $this->createQueryBuilder('a')
            ->select('count(a)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Обновляет параметр "isArchived".
     *
     * @param array $ids массив идентификаторов аккаунтов
     * @param int $value значение параметра (0 или 1)
     */
    public function updateIsArchivedValue(array $ids, int $value)
    {
        $queryBuilder = $this->createQueryBuilder('a');

        return $queryBuilder->update()
            ->set('a.isarchived', ':isArchived')
            ->set('a.modifydate', ':modifyDate')
            ->add('where', $queryBuilder->expr()->in('a.accountid', $ids))
            ->setParameter('isArchived', $value)
            ->setParameter('modifyDate', (new \DateTime())->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute();
    }

    public function getAccountOwnerForUser(UserOwnedInterface $loyalty, Usr $user)
    {
        if ($loyalty->getUserid()->getUserid() != $user->getUserId()) {
            $query = $this->_em->createQuery(
                '
                     SELECT
                       ua
                     FROM
                       AwardWallet\MainBundle\Entity\Useragent ua
                     WHERE
                       ua.agentid = :agent
                       AND ua.isapproved = TRUE
                       AND ua.clientid IS NOT NULL
                       AND ua.accesslevel IN (:levels)
                       AND ua.clientid = :accOwner
                   '
            )->setParameter('agent', $user)
                ->setParameter('accOwner', $loyalty->getUserid())
                ->setParameter('levels', Useragent::possibleOwnerAccessLevels());

            return $query->getOneOrNullResult();
        } else {
            return $loyalty->getUseragentid();
        }
    }

    /**
     * @param Account|int $account
     * @return int
     */
    public function countItinerariesByAccount($account)
    {
        $accountId = is_object($account) ? $account->getAccountid() : (int) $account;

        return (int) $this->_em->getConnection()->executeQuery(
            '
            SELECT SUM(cnt) as cnt FROM (
            	    SELECT IFNULL(COUNT(ts.TripSegmentID), 0) AS cnt 
            	    FROM TripSegment ts 
            	    JOIN Trip t ON ts.TripID = t.TripID 
            	    WHERE 
            	        t.AccountID = :accountId AND
            	        ts.DepDate > NOW() AND
            	        t.Hidden = 0 AND 
            	        ts.Hidden = 0 AND 
            	        t.Copied = 0
                    GROUP BY t.AccountID
            	UNION
            	    SELECT IFNULL(COUNT(r.ReservationID), 0) AS cnt 
            	    FROM Reservation r 
            	    WHERE 
            	        r.AccountID = :accountId AND
            	        r.CheckInDate > NOW() AND
            	        r.Hidden = 0 AND
            	        r.Copied = 0
                    GROUP BY r.AccountID
            	UNION
            	    SELECT IFNULL(COUNT(l.RentalID), 0) AS cnt 
            	    FROM Rental l 
            	    WHERE 
            	        l.AccountID = :accountId AND
            	        l.PickupDatetime > NOW() AND 
            	        l.Hidden = 0 AND 
            	        l.Copied = 0
                    GROUP BY l.AccountID
            	UNION
            	    SELECT IFNULL(COUNT(e.RestaurantID), 0) AS cnt 
            	    FROM Restaurant e 
            	    WHERE
                        e.AccountID = :accountId AND
                        e.StartDate > NOW() AND 
                        e.Hidden = 0 AND 
                        e.Copied = 0
            	    GROUP BY e.AccountID
            ) itineraries
        ',
            [':accountId' => $accountId],
            [\PDO::PARAM_INT]
        )->fetchColumn(0);
    }

    /**
     * @param int[] $providers
     * @return Account[][]
     */
    public function getPossibleAccountsForPossibleOwnersByProviders(array $providers, Usr $user, bool $withCustomAccounts = false): array
    {
        if (!$providers) {
            return [];
        }

        /** @var Owner[] $owners */
        $owners = $this->ownerRepository
            ->findAvailableOwners(OwnerRepository::FOR_ACCOUNT_ASSIGNMENT, $user, '', 0);

        if (!$owners) {
            return [];
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $e = $queryBuilder->expr();
        $ownerExprs = [];

        foreach ($owners as $owner) {
            $ownerExprs[] = ($familyMember = $owner->getFamilyMember()) ?
                $e->andX(
                    $e->eq('a.user', $owner->getUser()->getUserid()),
                    $e->eq('a.userAgent', $familyMember->getUseragentid())
                ) :
                $e->eq('a.user', $owner->getUser()->getUserid());
        }

        /** @var Account[] $accountQueryResult */
        $accountQueryResult = $queryBuilder
            ->select('a', 'p')
            ->from(Account::class, 'a')
            ->leftJoin('a.providerid', 'p')
            ->where(
                $e->andX(
                    $e->orX(...$ownerExprs),
                    $e->orX(
                        'a.providerid in (:providers)',
                        'a.providerid is null'
                    )
                )
            )
            ->setParameter('providers', $providers, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->execute();

        $accounts = [];

        foreach ($accountQueryResult as $account) {
            $isCustom = empty($account->getProviderid());

            if ($isCustom) {
                if ($withCustomAccounts) {
                    $accounts[$account->getProgramname()][$account->getOwner()->getIdentityString()][] = $account;
                }
            } else {
                $accounts[$account->getProviderid()->getProviderid()][$account->getOwner()->getIdentityString()][] = $account;
            }
        }

        return $accounts;
    }

    public function loadBrowserState(Account $account): ?string
    {
        $state = $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'select BrowserState from Account where AccountID = ?',
                [$account->getId()],
                [\PDO::PARAM_INT]
            )
            ->fetchOne();

        return false !== $state ? $state : null;
    }

    private function getEmptyFields()
    {
        $fields = [
            'TableName', 'ID', 'Login', 'Login2', 'Login3', 'ErrorCode', 'ErrorMessage', 'State', 'Pass', 'Description', 'Value', 'ExpirationDate', 'LastChangeDate', 'ModifyDate', 'PassChangeDate', 'SuccessCheckDate', 'LastCheckItDate', 'LastCheckHistoryDate', 'QueueDate', 'RawUpdateDate', 'UpdateDate', 'ChangeCount', 'ExpirationAutoSet', 'SavePassword', 'ErrorCount', 'Goal', 'ExpirationWarning', 'SubAccounts', 'UserAgentID', 'Question', 'DontTrackExpiration', 'NotRelated', 'LastDurationWithoutPlans', 'LastDurationWithPlans', 'IsActiveTab', 'comment', 'RawBalance', 'Balance', 'TotalBalance', 'LastBalance', 'ChangesConfirmed', 'ProviderCode', 'ProviderName', 'FullProviderName', 'LoginURL', 'ProgramName', 'DisplayName', 'Kind', 'ProviderID', 'AutoLogin', 'CanCheck', 'CanReceiveEmail', 'AllianceID', 'AllianceAlias', 'CanCheckBalance', 'Site', 'TradeMin', 'ExpirationDateNote', 'BalanceFormat', 'AllowFloat', 'FAQ', 'CanCheckExpiration', 'CanCheckItinerary', 'ExpirationAlwaysKnown', 'AAADiscount', 'CustomDisplayName', 'BarCode', 'IATACode', 'EliteLevelsCount', 'Currency', 'ProviderGroup', 'Code', 'DeepLinking', 'AvgDurationWithoutPlans', 'CanSavePassword', 'MobileAutoLogin', 'ExpirationUnknownNote', 'ProviderEngine', 'ProviderState', 'CheckInBrowser', 'UserID', 'AccountLevel', 'UserPictureVer', 'UserPictureExt', 'AutoGatherPlans', 'UserName', 'AgentComment', 'UserAgentPictureVer', 'UserAgentPictureExt', 'AccessLevel', 'ShareUserAgentID',
        ];

        return array_fill_keys($fields, null);
    }

    private function accountFields($accessLevelField = 'ash.AccessLevel', $agentField = 'ash.UserAgentID', $familyMemberTable = null)
    {
        $UserAgentPictureVer = 'ua.PictureVer';
        $UserAgentPictureExt = 'ua.PictureExt';
        $FamilyMemberName = 'null';

        if ($familyMemberTable) {
            $UserAgentPictureVer = "COALESCE({$familyMemberTable}.PictureVer, ua.PictureVer)";
            $UserAgentPictureExt = "COALESCE({$familyMemberTable}.PictureExt, ua.PictureExt)";
            $FamilyMemberName = "concat(trim(concat({$familyMemberTable}.FirstName, ' ', coalesce({$familyMemberTable}.MidName, ''))), ' ', {$familyMemberTable}.LastName)";
        }

        return "/*fields(*/
		CONVERT('Account' USING utf8) AS TableName                                               ,
		a.AccountID                   AS ID                                                      ,
		a.Login                                                                                  ,
		a.Login2                                                                                 ,
		a.Login3                                                                                 ,
		trim(trailing '.' FROM trim(trailing '0' FROM ROUND(a.Balance, 10)))      AS Balance     ,
		trim(trailing '.' FROM trim(trailing '0' FROM ROUND(a.TotalBalance, 10))) AS TotalBalance,
		a.ErrorCode                                                                              ,
		a.ErrorMessage                                                                           ,
		a.State                                                                                  ,
		a.Pass                                                                                   ,
		p.Code                                   AS ProviderCode                                 ,
		COALESCE( p.ShortName, a.ProgramName )   AS ProviderName                                 ,
		p.Name                                   AS FullProviderName                             ,
		COALESCE( p.LoginURL, a.LoginURL )       AS LoginURL                                     ,
		COALESCE( p.ProgramName, a.ProgramName ) AS ProgramName                                  ,
		CONVERT(NULL USING utf8)                 AS Description                                  ,
		CONVERT(NULL USING utf8)                 AS Value                                        ,
		NULL                                     AS CardNumber                                   ,
		NULL                                     AS TypeID                                       ,
		NULL AS TypeName,
		NULL                                     AS PIN                                          ,
		NULL                                     AS ConnectedAccount                             ,
		a.ExpirationDate                                                                                                           ,
		p.ProviderID                                                                                                               ,
		p.AutoLogin                                                                                                                ,
		a.DisableClientPasswordAccess,
		p.CanCheck                                                                                                                 ,
		p.CanReceiveEmail                                                                                                          ,
		p.AllianceID                                                                                                               ,
		al.Alias AS AllianceAlias                                                                                                  ,
		a.comment                                                                                                                  ,
		" . SQL_USER_NAME . " AS UserName                                                                                          ,
		u.UserID                                                                                                                   ,
		{$accessLevelField}        as AccessLevel                                                                                  ,
		p.CanCheckBalance                                                                                                          ,
		DATE_FORMAT(DATE(a.UpdateDate),'%M %e, %Y') AS UpdateDate                                                                  ,
		a.UpdateDate                                AS RawUpdateDate                                                               ,
		p.Site                                                                                                                     ,
		" . SQL_ACCOUNT_DISPLAY_NAME . " AS DisplayName                                                                    ,
		a.ExpirationAutoSet                                                                                                        ,
		p.ExpirationDateNote                                                                                                       ,
		a.LastChangeDate                                                                                                           ,
		a.ChangeCount                                                                                                              ,
		trim(trailing '.' FROM trim(trailing '0' FROM ROUND(a.LastBalance, 7))) AS LastBalance                                     ,
		trim(trailing '.' FROM trim(trailing '0' FROM ROUND(a.LastBalance, 7))) AS LastBalanceRaw,
		a.ChangesConfirmed                                                                                                         ,
		u.AccountLevel                                                                                                             ,
		u.Login         AS UserAccountLogin                                       ,
		u.Email         AS UserAccountEmail                                       ,
		p.TradeMin                                                                                                                 ,
		a.Goal                                                                                                                     ,
		p.BalanceFormat                                                                                                            ,
		COALESCE(p.AllowFloat, 1) as AllowFloat                                                                                    ,
		curr.Code AS ManualCurrencyCode,
		COALESCE(curr.Sign, curr.Name) AS ManualCurrencySign,
		a.ExpirationWarning                                                                                                        ,
		COALESCE(p.Kind, a.Kind, 1) AS Kind                                                                                        ,
		p.FAQ                                                                                                                      ,
		p.CanCheckExpiration                                                                                                       ,
		p.CanCheckItinerary																							               ,
		p.ExpirationAlwaysKnown                                                                                                    ,
		" . SQL_ACCOUNT_RAW_BALANCE . " AS RawBalance                                                                                                    ,
		p.AAADiscount                                                                                                              ,
		a.SavePassword                                                                                                             ,
		p.PasswordRequired,
		a.ErrorCount																										       ,
		p.ExpirationUnknownNote                                                                                                    ,
		a.SubAccounts                                                                                                              ,
		ua.Comment AS AgentComment                                                                                                 ,
		p.CustomDisplayName                                                                                                        ,
		p.BarCode                                                                                                                  ,
		p.IATACode                                                                                                                 ,
		{$agentField} AS ShareUserAgentID                                                                                          ,
		p.MobileAutoLogin                                                                                                          ,
		a.SuccessCheckDate                                                                                                         ,
		a.ModifyDate																											   ,
		a.PassChangeDate																										   ,
		u.PictureVer  AS UserPictureVer                                                                                            ,
		u.PictureExt  AS UserPictureExt                                                                                            ,
		{$UserAgentPictureVer} AS UserAgentPictureVer                                                                              ,
		{$UserAgentPictureExt} AS UserAgentPictureExt                                                                              ,
		p.EliteLevelsCount                                                                                                         ,
		a.CustomEliteLevel,
		a.UserAgentID                                                                                                              ,
		p.Currency                                                                                                                 ,
		currProvider.Name AS ProviderCurrencyName                                                                                  ,
		a.Question                                                                                                                 ,
		p.ProviderGroup                                                                                                            ,
		a.DontTrackExpiration                                                                                                      ,
		a.NotRelated                                                                                                               ,
		COALESCE(p.CheckInBrowser, 0) AS CheckInBrowser                                                                            ,
		p.CheckInMobileBrowser                                                                                                     ,
		p.AutologinV3                                                                                                              ,
		a.LastDurationWithoutPlans																								   ,
		a.LastDurationWithPlans																									   ,
		p.Code																													   ,
		u.AutoGatherPlans																										   ,
		a.LastCheckItDate                                                                                                          ,
		a.LastCheckHistoryDate                                                                                                     ,
		p.DeepLinking,
		p.AvgDurationWithoutPlans,
		p.Engine AS ProviderEngine,
		p.State as ProviderState,
		p.ManualUpdate AS ProviderManualUpdate,
		a.QueueDate,
		a.BalanceWatchStartDate,
		a.IsActiveTab,
		a.IsArchived,
		a.Disabled,
		a.DisableBackgroundUpdating,
		p.CanSavePassword,
		{$FamilyMemberName} AS FamilyMemberName,
		a.BackgroundCheck AS BackgroundCheck,
		p.BackgroundColor,
		p.FontColor,
		p.AccentColor,
		p.Border_LM,
		p.Border_DM,
		p.KeyWords,
		p.BlogTagsID,
		p.BlogPostID,
		p.BlogIdsMileExpiration,
		p.BlogIdsPromos,
		p.BlogIdsMilesPurchase,
		p.BlogIdsMilesTransfers,
		null as LinkedToAccountID,
		null as CustomFields,
		a.PwnedTimes
		/*)fields*/";
    }

    private function couponFields($accessLevelField = 'ash.AccessLevel', $agentField = 'ash.UserAgentID', $familyMemberTable = null)
    {
        $UserAgentPictureVer = 'ua.PictureVer';
        $UserAgentPictureExt = 'ua.PictureExt';
        $FamilyMemberName = 'null';

        if ($familyMemberTable) {
            $UserAgentPictureVer = "COALESCE({$familyMemberTable}.PictureVer, ua.PictureVer)";
            $UserAgentPictureExt = "COALESCE({$familyMemberTable}.PictureExt, ua.PictureExt)";
            $FamilyMemberName = "concat(trim(concat({$familyMemberTable}.FirstName, ' ', coalesce({$familyMemberTable}.MidName, ''))), ' ', {$familyMemberTable}.LastName)";
        }

        return "/*couponfields(*/ CONVERT('Coupon' USING utf8) AS TableName       ,
		c.ProviderCouponID           AS ID              ,
		CONVERT(NULL USING utf8)     AS Login           ,
		NULL                         AS Login2          ,
		NULL                         AS Login3          ,
		c.Value                      AS Balance         ,
		0                            AS TotalBalance    ,
		CONVERT(NULL USING utf8)     AS ErrorCode       ,
		CONVERT(NULL USING utf8)     AS ErrorMessage    ,
		CONVERT(NULL USING utf8)     AS State           ,
		CONVERT(NULL USING utf8)     AS Pass            ,
		NULL                         AS ProviderCode    ,
		c.ProgramName                AS ProviderName    ,
		c.ProgramName                AS FullProviderName,
		NULL                         AS LoginURL        ,
		c.ProgramName                                   ,
		c.Description AS Description                    ,
		c.Value                                         ,
		c.CardNumber                                    ,
		c.TypeID                                        ,
		c.TypeName,
		c.PIN                                           ,
		c.AccountID              AS ConnectedAccount            ,
		c.ExpirationDate         AS ExpirationDate              ,
		NULL                     AS ProviderID                  ,
		0                        AS AutoLogin                   ,
		0                        AS DisableClientPasswordAccess ,
		0                        AS CanCheck                    ,
		0                        AS CanReceiveEmail             ,
		0                        AS AllianceID                  ,
		NULL                     AS AllianceAlias               ,
		CONVERT(NULL USING utf8) AS COMMENT                     ,
		" . SQL_USER_NAME . "        AS UserName                ,
		u.UserID                                                ,
		{$accessLevelField} AS AccessLevel                      ,
		0               AS CanCheckBalance                      ,
		NULL            AS UpdateDate                           ,
		NULL            AS RawUpdateDate                        ,
		NULL            AS Site                                 ,
		" . SQL_COUPON_DISPLAY_NAME . ' AS DisplayName                          ,
		' . EXPIRATION_UNKNOWN . "   AS ExpirationAutoSet       ,
		NULL            AS ExpirationDateNote                   ,
		NULL            AS LastChangeDate                       ,
		NULL            AS ChangeCount                          ,
		NULL            AS LastBalance                          ,
		NULL            AS LastBalanceRaw,
		NULL            AS ChangesConfirmed                     ,
		u.AccountLevel                                          ,
		u.Login         AS UserAccountLogin                                ,
		u.Email         AS UserAccountEmail                                ,
		NULL            AS TradeMin                                        ,
		NULL            AS Goal                                            ,
		NULL            AS BalanceFormat                                   ,
		1               AS AllowFloat                                      ,
		curr.Code       AS ManualCurrencyCode                              ,
		COALESCE(curr.Sign, curr.Name) AS ManualCurrencySign               ,
		NULL            AS ExpirationWarning                               ,
		c.Kind          AS Kind                                            ,
		NULL            AS FAQ                                             ,
		0               AS CanCheckExpiration                              ,
		0			   AS CanCheckItinerary                                ,
		0               AS ExpirationAlwaysKnown                           ,
		0               AS RawBalance                                      ,
		0               AS AAADiscount                                     ,
		NULL            AS SavePassword                                    ,
		NULL as PasswordRequired,
		NULL			   AS ErrorCount								   ,
		NULL            AS ExpirationUnknownNote                           ,
		0               AS SubAccounts                                     ,
		NULL            AS AgentComment                                    ,
		0               AS CustomDisplayName                               ,
		NULL            AS BarCode                                         ,
		NULL            AS IATACode                                        ,
		{$agentField} AS ShareUserAgentID                                  ,
		NULL            AS MobileAutoLogin                                 ,
		NULL            AS SuccessCheckDate                                ,
		NULL			   AS PassChangeDate							   ,
		NULL			   AS ModifyDate								   ,
		u.PictureVer    AS UserPictureVer                                  ,
		u.PictureExt    AS UserPictureExt                                  ,
		{$UserAgentPictureVer}  AS UserAgentPictureVer                     ,
		{$UserAgentPictureExt}  AS UserAgentPictureExt                     ,
		NULL            AS EliteLevelsCount                                ,
		NULL as CustomEliteLevel,
		c.UserAgentID                                                      ,
		NULL AS Currency                                                   ,
		NULL AS ProviderCurrencyName                                       ,
		NULL AS Question                                                   ,
		NULL AS ProviderGroup                                              ,
		DontTrackExpiration                                        ,
		NULL AS NotRelated                                                 ,
		NULL AS CheckInBrowser                                             ,
		NULL AS CheckInMobileBrowser                                       ,
		NULL AS AutologinV3                                                ,
		NULL AS LastDurationWithoutPlans								   ,
		NULL AS LastDurationWithPlans									   ,
		NULL AS Code													   ,
		NULL AS AutoGatherPlans											   ,
		NULL AS LastCheckItDate											   ,
		NULL AS LastCheckHistoryDate									   ,
		0       DeepLinking												   ,
		NULL AS AvgDurationWithoutPlans									   ,
		NULL AS ProviderEngine                                             ,
		NULL AS ProviderState                                              ,
		NULL AS ProviderManualUpdate                                       ,
		NULL AS QueueDate												   ,
		NULL as BalanceWatchStartDate,
		NULL AS IsActiveTab												   ,
		c.IsArchived AS IsArchived,
		NULL AS Disabled,
		NULL AS DisableBackgroundUpdating,
		NULL AS CanSavePassword,
		{$FamilyMemberName} AS FamilyMemberName,
		NULL AS BackgroundCheck,
		NULL AS BackgroundColor,
		NULL AS FontColor,
		NULL AS AccentColor,
		0 AS Border_LM,
		0 AS Border_DM,
		NULL AS KeyWords,
		NULL AS BlogTagsID,
		NULL AS BlogPostID,
		ct.BlogIdsMileExpiration,
		NULL AS BlogIdsPromos,
		NULL AS BlogIdsMilesPurchase,
		NULL AS BlogIdsMilesTransfers,
		c.AccountID as LinkedToAccountID,
		c.CustomFields as CustomFields,
		NULL AS PwnedTimes
		/*)couponfields*/";
    }
}
