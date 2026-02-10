<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\BoardingPass\BoardingPass;
use AwardWallet\Common\API\Email\V2\Loyalty\HistoryField as V2HistoryField;
use AwardWallet\Common\API\Email\V2\Loyalty\HistoryRow as V2HistoryRow;
use AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Entity\Providersignal;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usersignal;
use AwardWallet\MainBundle\Entity\Usersignalattribute;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\RewardsActivity;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\StatementParsed;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\GmailForwarding;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\ProblemRecognizingSubmission;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\HistoryProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryField;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryRow;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Service\Account\RecentlyList;
use AwardWallet\MainBundle\Service\CreditCards\UserCreditCardsService;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor as OTC;
use AwardWallet\Strings\Strings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class CallbackProcessor
{
    public const SAVE_MESSAGE_FAIL = 'fail';
    public const SAVE_MESSAGE_SUCCESS = 'success';
    public const SAVE_MESSAGE_MISSED = 'missed';
    /**
     * @var EmailScannerApi
     */
    protected $scannerApi;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var UsrRepository
     */
    private $userRep;
    /**
     * @var ProviderRepository
     */
    private $providerRep;
    /**
     * @var UseragentRepository
     */
    private $userAgentRep;
    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var AdManager
     */
    private $adManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Mailer
     */
    private $mailer;
    /**
     * @var RecentlyList
     */
    private $recentlyList;
    /**
     * @var ItineraryProcessor
     */
    private $itineraryProcessor;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var UtilBusiness
     */
    private $businessProcessor;
    /**
     * @var HistoryProcessor
     */
    private $historyProcessor;
    /**
     * @var GlobalVariables
     */
    private $globalVariables;
    /**
     * @var BoardingPassProcessor
     */
    private $bpp;
    /**
     * @var OTC\EmailProcessor
     */
    private $epp;

    private BalanceProcessor $balanceProcessor;

    private AccountFactory $accountFactory;

    private \Memcached $memcached;
    /**
     * @var TripRepository
     */
    private $tripRep;
    private UserCreditCardsService $userCreditCardsService;

    private EmailAddressManager $eaManager;

    private StatementMatcher $sm;

    private StatementSaver $ss;

    private \HttpDriverInterface $httpDriver;
    private ObjectRepository $userSignalRepository;
    private ObjectRepository $providerSignalRepository;

    public function __construct(
        Connection $connection,
        ManagerRegistry $doctrine,
        LocalizeService $localizer,
        RouterInterface $router,
        $host,
        LoggerInterface $logger,
        Mailer $mailer,
        AdManager $adManager,
        RecentlyList $recentlyList,
        ItineraryProcessor $itineraryProcessor,
        UserManager $userManager,
        EventDispatcherInterface $dispatcher,
        UtilBusiness $businessProcessor,
        HistoryProcessor $historyProcessor,
        GlobalVariables $globalVariables,
        EmailScannerApi $scannerApi,
        BoardingPassProcessor $bpp,
        OTC\EmailProcessor $epp,
        BalanceProcessor $balanceProcessor,
        AccountFactory $accountFactory,
        \Memcached $memcached,
        UserCreditCardsService $userCreditCardsService,
        EmailAddressManager $eaManager,
        StatementMatcher $sm,
        StatementSaver $ss,
        \HttpDriverInterface $httpDriver
    ) {
        $this->connection = $connection;
        $this->em = $doctrine->getManager();
        $this->userRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->providerRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->userAgentRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $this->userSignalRepository = $doctrine->getRepository(Usersignal::class);
        $this->providerSignalRepository = $doctrine->getRepository(Providersignal::class);
        $this->localizer = $localizer;
        $this->router = $router;
        $this->router->getContext()->setHost($host);
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->adManager = $adManager;
        $this->recentlyList = $recentlyList;
        $this->itineraryProcessor = $itineraryProcessor;
        $this->userManager = $userManager;
        $this->dispatcher = $dispatcher;
        $this->businessProcessor = $businessProcessor;
        $this->historyProcessor = $historyProcessor;
        $this->globalVariables = $globalVariables;
        $this->scannerApi = $scannerApi;
        $this->bpp = $bpp;
        $this->epp = $epp;
        $this->balanceProcessor = $balanceProcessor;
        $this->accountFactory = $accountFactory;
        $this->memcached = $memcached;
        $this->tripRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Trip::class);
        $this->userCreditCardsService = $userCreditCardsService;
        $this->eaManager = $eaManager;
        $this->sm = $sm;
        $this->ss = $ss;
        $this->httpDriver = $httpDriver;
    }

    public function process(ParseEmailResponse $data, EmailOptions $info, ?Owner $owner, $account): string
    {
        if ($info->isCycled()) {
            $this->logger->info('Cycled email');

            return self::SAVE_MESSAGE_FAIL;
        }

        $this->assignRecipientParts($info, $owner);

        if ($owner === null && $account === null) {
            $owner = $this->locateOwnerByRecipientParts($info->recipientParts);

            if ($owner === null) {
                $this->logger->info('user not found');

                return self::SAVE_MESSAGE_FAIL;
            }
        }

        /* allow gpt
        if ($info->source->isGpt() && (empty($owner) || !$owner->getUser()->hasRole('ROLE_STAFF'))) {
            return self::SAVE_MESSAGE_MISSED;
        }
        */

        $result = self::SAVE_MESSAGE_FAIL;

        if ($owner) {
            $user = $owner->getUser();

            if ($data->providerCode) {
                $this->throttle($user->getId(), $data->providerCode);
                $this->lock($user->getId(), $data->providerCode);
            }

            if (!empty($data->signal)) {
                return $this->processSignal($data, $user);
            }

            if ($info->source->getSource() == ParsedEmailSource::SOURCE_PLANS && ($this->checkGmailConfirmation($data, $info, $user) || $this->checkProtonConfirmation($data, $info, $user))) {
                return self::SAVE_MESSAGE_SUCCESS;
            }

            if ($user->isBusiness()) {
                $this->logger->info('using business saver');

                return $this->businessProcessor->processBusinessMessage($data, $info, $user->getId());
            }
            //            $this->userManager->loadToken($user, false, UserManager::LOGIN_TYPE_ADMINISTRATIVE);
            //            AbstractController::setRegionalSettings($this->localizer);

            if (!empty($data->itineraries) && $user->getAutogatherplans() && $this->itineraryProcessor->process($data, $owner, $info)) {
                $result = self::SAVE_MESSAGE_SUCCESS;
            }

            if (!empty($data->cardPromo)) {
                $this->logger->info('EmailCallbackProcessor cardPromo', ['cardName' => $data->cardPromo->cardName]);
                $this->userCreditCardsService->processEmailCallback($user, $data);
            }
        }

        if ($owner && !empty($data->oneTimeCodes)) {
            $emailDate = strtotime($info->parser->getDate());

            if (is_array($data->oneTimeCodes)
                && is_string($data->oneTimeCodes[0])
                && strlen($data->oneTimeCodes[0]) > 0
                && $emailDate > strtotime('-10 minutes')) {
                if ($this->epp->process($data->providerCode, $owner->getUser(), $data->oneTimeCodes[0])) {
                    $result = self::SAVE_MESSAGE_SUCCESS;
                }

                if (empty($data->loyaltyAccount) && $data->fromProvider) {
                    $data->loyaltyAccount = new LoyaltyAccount();
                    $data->loyaltyAccount->providerCode = $data->providerCode;
                    $data->loyaltyAccount->isMember = true;
                }
            }
        }

        if (($owner || $account) && !empty($data->loyaltyAccount) && $info->source->getSource() != ParsedEmailSource::SOURCE_SCANNER
            && ( // todo remove restrictions
                in_array($data->providerCode, ['aa', 'delta', 'deltacorp', 'rapidrewards', 'mileageplus', 'testprovider', 'perksplus'])
            || preg_match('/^veresch(\+[^@]+)?@gmail\.com$/', $info->parser->getCleanTo()) > 0)) {
            if ($this->processStatement($data->providerCode, $data, $info, $owner, $account)) {
                $result = self::SAVE_MESSAGE_SUCCESS;
            } elseif ($result === self::SAVE_MESSAGE_FAIL) {
                $result = self::SAVE_MESSAGE_MISSED;
            }
        }

        if ($owner) {
            if (!empty($data->boardingPasses)) {
                foreach ($data->boardingPasses as $bp) {
                    /** @var BoardingPass $bp */
                    if (!empty($bp->attachmentFileName)) {
                        $info->refreshEmail($data->email);

                        break;
                    }
                }

                if ($this->bpp->process($data->boardingPasses, $info->parser, $owner)) {
                    $result = self::SAVE_MESSAGE_SUCCESS;
                }
            }

            if (!empty($data->awardRedemption) && !empty($data->metadata->mailboxId) && $data->providerCode == 'aa') {
                $this->logger->info('Try process aa award redemption');

                if ($this->processAaAwardRedemption($data)) {
                    $result = self::SAVE_MESSAGE_SUCCESS;
                }
            }

            if ($data->fromProvider) {
                $info->refreshEmail($data->email);
                $this->forwardEmail($info, $owner->getUser(), $owner->getFamilyMember(), $account);
            }
            // disable bc of spam
            /*
            elseif ($result === self::SAVE_MESSAGE_FAIL && $data->status === 'review') {
                $this->sendParseError($info, $owner->getUser(), $owner->getFamilyMember());
            }
            */
            // should we pass userAgent send emails to userAgent.Email if any ?
        }

        if ($data->fromProvider && $data->status == 'success' && $data->metadata && $data->metadata->from && $data->metadata->from->email) {
            $types = [
                !empty($data->itineraries) ? 1 : 0,
                (!empty($data->loyaltyAccount) && isset($data->loyaltyAccount->balance)) ? 1 : 0,
                (!empty($data->loyaltyAccount) && !isset($data->loyaltyAccount->balance)) ? 1 : 0,
                !empty($data->oneTimeCodes) ? 1 : 0,
                !empty($data->boardingPasses) ? 1 : 0,
            ];
            $types[] = array_sum($types) === 0 ? 1 : 0;
            $this->eaManager->write($data->metadata->from->email, $types);
        }

        return $result;
    }

    private function processSignal(ParseEmailResponse $data, Usr $user): string
    {
        $signal = $data->signal;

        if (empty($signal->name)) {
            return self::SAVE_MESSAGE_FAIL;
        }
        $date = $data->metadata->receivedDateTime ?? null;

        if (!$date) {
            return self::SAVE_MESSAGE_FAIL;
        }
        $date = new \DateTime($date);
        $this->logger->info('processing signal');
        $properties = [];

        if (!empty($signal->properties)) {
            foreach ($signal->properties as $item) {
                $properties[$item->name] = $item->value;
            }
        }

        try {
            $providerSignal = $this->providerSignalRepository->findOneBy(['code' => $signal->name]);

            if (!$providerSignal) {
                return self::SAVE_MESSAGE_FAIL;
            }
            $userSignal = $this->userSignalRepository->findOneBy(['providerSignalId' => $providerSignal, 'userId' => $user]);

            if (!$userSignal) {
                $userSignal = new Usersignal();
                $userSignal
                    ->setProviderSignalId($providerSignal)
                    ->setUserId($user);
                $this->em->persist($userSignal);
            }
            $userSignal->setDetectedOn($date);

            foreach ($userSignal->getAttributes() as $userSignalAttribute) {
                $this->em->remove($userSignalAttribute);
            }
            $this->em->flush();
            $userSignal->setAttributes([]);

            foreach ($providerSignal->getAttributes() as $signalAttribute) {
                if (isset($properties[$signalAttribute->getName()])) {
                    $userSignalAttribute = new Usersignalattribute();
                    $userSignalAttribute
                        ->setUserSignalId($userSignal)
                        ->setSignalAttributeId($signalAttribute)
                        ->setValue($properties[$signalAttribute->getName()]);
                    $this->em->persist($userSignalAttribute);
                }
            }
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->notice($e->getMessage());

            return self::SAVE_MESSAGE_FAIL;
        }

        return self::SAVE_MESSAGE_SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function processStatement(string $providerCode, ParseEmailResponse $data, EmailOptions $info, ?Owner $owner, &$found): bool
    {
        /** @var LoyaltyAccount $statement */
        $statement = $data->loyaltyAccount;
        $foundAa = null;

        if ($providerCode == 'aa') {
            $aaRegEx = '/@aa[.]com$/';
            $this->logger->info('updating with aa statement');

            if (!empty($owner->getEmail()) && preg_match($aaRegEx, $owner->getEmail())) {
                $this->logger->info('update rejected by owner address');

                return false;
            }

            foreach ($data->metadata->to as $address) {
                if (!empty($address->email) && preg_match($aaRegEx, $address->email) > 0) {
                    $this->logger->info('update rejected by recipient address');

                    return false;
                }
            }

            if ($owner->getUser()->hasRole('ROLE_DO_NOT_COMMUNICATE')) {
                $this->logger->info('update rejected by DNC role');

                return false;
            }

            if ($foundAa = $this->sm->matchCustomAa($owner, $statement)->acc) {
                $found = $foundAa->getId();
            } else {
                return false;
            }
        }

        /** @var Provider $provider */
        $provider = $this->providerRep->findOneByCode($providerCode);
        $numberProperty = null;

        if ($provider) {
            foreach ($provider->getProperties() as $property) {
                if ($property->getKind() === 1) {
                    $numberProperty = $property;

                    break;
                }
            }
        }

        if ($numberProperty) {
            $this->logger->info('found number property ' . $numberProperty->getCode());
        }
        $accountLogin = $info->recipientParts['AccountLogin'] ?? null;
        $properties = [];

        if (is_array($statement->properties)) {
            foreach ($statement->properties as $property) {
                $properties[$property->code] = $property->value;
            }
        }

        if ($statement->login) {
            if (empty($statement->loginMask)) {
                $properties['Login'] = $statement->login;
            } elseif ($statement->loginMask === 'left') {
                $properties['PartialLogin'] = $statement->login . '$';
            } elseif ($statement->loginMask === 'right') {
                $properties['PartialLogin'] = '^' . $statement->login;
            }
        }

        if ($statement->number && isset($numberProperty)) {
            $c = $numberProperty->getCode();

            if (empty($statement->numberMask)) {
                $properties[$c] = $statement->number;
            } elseif ($statement->numberMask === 'left') {
                $properties['Partial' . $c] = $statement->number . '$';
            } elseif ($statement->numberMask === 'right') {
                $properties['Partial' . $c] = '^' . $statement->number;
            }
        }

        if (!empty($statement->expirationDate) && (($exp = strtotime($statement->expirationDate)) > strtotime('2010-01-01'))) {
            $properties['AccountExpirationDate'] = $exp;
        }
        $sourceEmail = $info->source ? $info->source->getUserEmail() : null;
        $account = is_numeric($found) ? $this->getAccountInfo($found) : $this->findAccount($owner, $provider, $numberProperty, $properties, $accountLogin, $sourceEmail);

        if (null === $account) {
            return false;
        }
        $found = $account;
        /** @var Account $accountEntity */
        $accountEntity = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($account['ID']);

        if ($providerCode != 'aa' && $accountEntity->getProviderid()->getCode() != $providerCode) {
            return false;
        }

        if (!is_null($statement->balance)) {
            $accountEntity->setEmailparsedate(new \DateTime());
            $this->em->flush();

            $old = false;
            $balanceDate = $statement->balanceDate ?? $data->metadata->receivedDateTime;

            if (!empty($balanceDate)) {
                $balanceDateTime = new \DateTime($balanceDate);
                $maxAge = '-30 days';

                if ($providerCode === 'perksplus') {
                    $maxAge = '-90 days';
                }

                if ($balanceDateTime < new \DateTime($maxAge)) {
                    $this->logger->info('statement is too old');
                    $old = true;
                }

                if (!empty($accountEntity->getUpdatedate()) && $accountEntity->getUpdatedate() > $balanceDateTime) {
                    $this->logger->info('statement is older than account update date');
                    $old = true;
                }
            }

            if (!$old) {
                if (!empty($foundAa)) {
                    $saved = $this->ss->save($foundAa, $statement, ($date = $data->metadata->receivedDateTime) ? new \DateTime($date) : null);
                } else {
                    $saved = $this->saveAccountBalance($account, $accountEntity, $provider, $statement, $properties, $numberProperty, $sourceEmail);

                    if ($owner && !empty($saved['Updated']) && !$info->silent) {
                        $this->sendBalanceUpdated($saved, $owner->getUser());
                    }
                }
            }
        }

        if (!empty($statement->history)) {
            $this->historyProcessor->saveAccountHistory($account['ID'], $this->convertHistory($provider, $statement), true);
            $saved = true;
        }

        if ($accountEntity) {
            $this->dispatcher->dispatch(new AccountUpdateEvent($accountEntity, AccountUpdateEvent::SOURCE_EMAIL));
        }

        return !empty($saved);
    }

    private function convertHistory(Provider $provider, LoyaltyAccount $statement)
    {
        $columns = $this->globalVariables->getAccountChecker($provider)->GetHistoryColumns();

        return (new History())
            ->setRange(History::HISTORY_INCREMENTAL)
            ->setRows(
                array_map(
                    function (V2HistoryRow $row) use ($columns) {
                        return (new HistoryRow())->setFields(
                            array_reduce($row->fields, function ($fields, V2HistoryField $field) use ($columns) {
                                $name = $field->name;
                                $value = $field->value;

                                if (isset($columns[$name])) {
                                    if ($columns[$name] == 'PostingDate') {
                                        $date = date_create_from_format('Y-m-d\TH:i:s', $value);
                                        $value = $date ? $date->getTimestamp() : null;
                                    }

                                    if (!empty($value)) {
                                        $fields[] = new HistoryField($name, $value);
                                    }
                                }

                                return $fields;
                            }, [])
                        );
                    },
                    $statement->history
                )
            );
    }

    private function saveAccountBalance(array $account, Account $accountEntity, Provider $provider, LoyaltyAccount $data, array $properties, ?Providerproperty $numberProperty, $sourceEmail)
    {
        if (!empty($account['CheckedBy']) && $account['CheckedBy'] != Account::CHECKED_BY_EMAIL
            && !empty($account['UpdateDate']) && !empty($data->balanceDate) && (strtotime($data->balanceDate) < strtotime($account['UpdateDate']))) {
            $this->logger->info(sprintf('balance date %s is older than update date %s, skipping', $data->balanceDate, $account['UpdateDate']));

            return null;
        }

        if ($data->balance === null && !empty($data->history)) {
            $properties["Balance"] = $account["Balance"];
        }

        if ($account["Updated"] = $this->balanceProcessor->saveAccountBalance($accountEntity, $data->balance)) {
            $this->dispatcher->dispatch(new AccountBalanceChangedEvent($accountEntity));
        }
        $updatedBy = $accountEntity->getCheckedby();

        $auditor = new \AccountAuditor();
        $report = new \AccountCheckReport();
        $report->balance = $data->balance;
        $report->errorCode = ACCOUNT_CHECKED;
        $allowedProps = [];

        foreach ($provider->getProperties() as $prop) {
            $allowedProps[$prop->getCode()] = $prop->getName();
        }
        $existingProps = [];

        /** @var Accountproperty $prop */
        foreach ($accountEntity->getProperties() as $prop) {
            $existingProps[$prop->getProviderpropertyid()->getCode()] = $prop->getVal();
        }
        $propsToSave = array_intersect_key($properties, array_merge($allowedProps, ["AccountExpirationDate" => 0]));

        if (isset($numberProperty) && empty($properties[$numberProperty->getCode()]) && isset($existingProps[$numberProperty->getCode()])) {
            $propsToSave[$numberProperty->getCode()] = $existingProps[$numberProperty->getCode()];
        }
        $report->properties = $propsToSave;
        $this->logger->debug("properties to save: " . var_export($report->properties, true));
        $options = new \AuditorOptions();
        $options->checkIts = false;
        $options->checkedBy = Account::CHECKED_BY_EMAIL;
        $toSave = new \Account($account["ID"]);
        $report->account = $toSave;
        $report->filter();

        if (!$auditor->save($toSave, $report, $options)) {
            throw new \Exception("account not saved for some reason");
        } else {
            $this->logger->info("account saved");
            $auditor->nullQueueDate($toSave);

            if ($updatedBy != Account::CHECKED_BY_EMAIL && !isset($propsToSave["AccountExpirationDate"])) {
                $auditor->nullExpirationDate($toSave);
            }

            if ($sourceEmail && $accountEntity->getState() == ACCOUNT_PENDING) {
                $accountEntity->setSourceEmail($sourceEmail);
                $this->em->flush();
            }
            $account["Balance"] = $data->balance;

            if (isset($numberProperty) && isset($propsToSave[$numberProperty->getCode()])) {
                $account["AccountNumber"] = $propsToSave[$numberProperty->getCode()];
            }

            if (isset($properties["Name"])) {
                $account["OwnerName"] = $properties["Name"];
            }
        }

        return $account;
    }

    private function findAccount(Owner $owner, Provider $provider, ?Providerproperty $numberProperty, array $properties, ?string $accountLogin, $emailSource)
    {
        $numberId = isset($numberProperty) ? $numberProperty->getProviderpropertyid() : 0;
        $alias = $owner->getFamilyMember() !== null ? $owner->getFamilyMember()->getAlias() : null;
        $q = $this->connection->executeQuery(
            "
			select a.AccountID as ID,
				   a.UserID,
				   a.UserAgentID,
				   a.Login,
				   a.Balance,
				   a.State,
				   a.CheckedBy,
				   a.UpdateDate,
				   ap.Val as AccountNumber,
				   ua.Alias,
				   ua.ClientID,
				   ua.Email,
				   ua.SendEmails
			from Account a
			left join AccountProperty ap on a.AccountID = ap.AccountID and ap.ProviderPropertyID = ?
			left join UserAgent ua on a.UserAgentID = ua.UserAgentID
			where UserID = ? and ProviderID = ?
			union
			select a.AccountID as ID,
				   a.UserID,
				   a.UserAgentID,
				   a.Login,
				   a.Balance,
				   a.State,
				   a.CheckedBy,
				   a.UpdateDate,
				   ap.Val as AccountNumber,
				   ua.Alias,
				   ua.ClientID,
				   ua.Email,
				   ua.SendEmails
			from UserAgent ua
			inner join AccountShare ash	on ua.UserAgentID = ash.UserAgentID
			inner join Account a on ash.AccountID = a.AccountID
			left join AccountProperty ap on a.AccountID = ap.AccountID and ap.ProviderPropertyID = ?
			where ua.AgentID = ? and a.ProviderID = ?",
            [$numberId, $owner->getUser()->getUserid(), $provider->getProviderid(), $numberId, $owner->getUser()->getUserid(), $provider->getProviderid()],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        $account = null;
        /**
         * priority:
         * 	1 - partial match
         *  2 - match by agent's alias
         * 	3 - match by number in address
         *  4 - full match
         *  11, 13, 14 - match by number AND alias - 10 + priority
         *  101, 102, 103, 104, 111, 113, 114 - match by number AND userid.
         */
        $priority = 0;
        $matches = 0;
        $single = null;
        $numberCode = $numberProperty ? $numberProperty->getCode() : null;

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if (is_null($single)) {
                $single = $row;
            } else {
                $single = false;
            }
            $this->logger->info('Comparing account: ' . $row["ID"] . ', ' . $row["Login"] . ', ' . $row["AccountNumber"] . ', ' . $row['State']);
            $matches++;

            if ($row['State'] == ACCOUNT_IGNORED) {
                continue;
            }
            // current priority
            $pp = 0;

            if (isset($properties["PartialLogin"]) && preg_match("/" . $properties["PartialLogin"] . "/i", $row["Login"])
                || isset($numberCode) && isset($properties["Partial$numberCode"]) && preg_match("/" . $properties["Partial$numberCode"] . "/i", $row["AccountNumber"])) {
                $pp = 1;
            }

            if (isset($accountLogin) && $this->compareNumbers($provider, $accountLogin, $row['Login'])) {
                $pp = 3;
            }

            if (isset($properties["Login"]) && $this->compareNumbers($provider, $properties['Login'], $row['Login'])
                || isset($numberCode) && isset($properties[$numberCode]) && $this->compareNumbers($provider, $properties[$numberCode], $row['AccountNumber'])) {
                $pp = 4;
            }

            if (isset($alias) && !empty($row["Alias"]) && strcasecmp($alias, $row["Alias"]) == 0) {
                if ($pp > 0) {
                    $pp += 10;
                } else {
                    $pp = 2;
                }
            }

            if ($row["UserID"] == $owner->getUser()->getUserid() && $pp > 0) {
                $pp += 100;
            }

            if ($pp > 0 && $pp === $priority) {
                $account = null;
            }

            if ($pp > $priority) {
                $account = $row;
                $priority = $pp;
            }
        }

        if ($single && empty($properties['Login']) && empty($properties[$numberCode])
                       && empty($properties['PartialLogin']) && empty($properties["Partial$numberCode"])
                       && empty($accountLogin)) {
            $this->logger->info('no properties to match, selected the only account present');
            $account = $single;
        }

        if (empty($account) && $priority > 0) {
            $this->logger->info('Multiple partial matches found');

            return null;
        }

        if (isset($account)) {
            if (empty($account["UserAgentID"])) {
                $account["UserAgentID"] = 0;
            }
            $this->logger->info('Account found: ' . var_export($account, true));
            $account["Pending"] = $account["State"] == ACCOUNT_PENDING;
            unset($account["State"]);

            if ($priority === 1) {
                $account["Partial"] = true;
            }

            return $account;
        }

        if ($matches > 0) {
            $this->logger->info('skipping creating pending');

            return null;
        }
        $this->logger->info('Account not found, creating pending account');
        $login = null;

        if (isset($properties["Login"])) {
            $login = $properties["Login"];
        }

        if (!isset($login) && isset($properties["PartialLogin"])) {
            $login = $this->clearPartialRegexp($properties["PartialLogin"]);
        }
        /*
        if (!isset($login)) {
            $this->logger->info('Not enough info for pending account');
            return null;
        }
        */
        $ua = 0;

        if (isset($properties["Name"])) {
            $similarity = $this->compareNames($properties["Name"], $owner->getUser()->getFirstname() . " " . $owner->getUser()->getLastname());

            if ($similarity !== true) {
                $agents = $this->userAgentRep->findBy([
                    'agentid' => $owner->getUser()->getUserid(),
                ]);

                foreach ($agents as $agent) {
                    if ($agent->getClientid() == "") {
                        $sim = $this->compareNames($properties["Name"], $agent->getFirstname() . " " . $agent->getLastname());

                        if ($sim === true || $sim > $similarity) {
                            $similarity = $sim;
                            $ua = $agent->getUseragentid();

                            if ($sim === true) {
                                break;
                            }
                        }
                    }
                }
            }

            if ($ua != 0) {
                $this->logger->debug('found useragent ' . $ua . ' with similarity ' . ($similarity === true ? '100%' : $similarity));
            }
        }

        if ($owner->getUser()->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS) {
            $this->logger->info('dont create account for business');

            return null;
        }

        return $this->createAccount($owner->getUser(), $provider, $login, $ua, $emailSource);
    }

    private function compareNumbers(Provider $provider, $cmp1, $cmp2)
    {
        if (strcmp($provider->getCode(), 'rapidrewards') === 0) {
            $cmp1 = preg_replace("/^0+/", "", $cmp1);
            $cmp2 = preg_replace("/^0+/", "", $cmp2);
        }

        return strcasecmp($cmp1, $cmp2) === 0;
    }

    private function getAccountInfo($accountId)
    {
        if (!empty($accountId)) {
            $data = $this->connection->executeQuery('
            select a.AccountID as ID,
				   a.UserID,
				   a.UserAgentID,
				   a.Login,
				   a.Balance,
				   a.State,
				   a.CheckedBy,
				   a.UpdateDate,
				   ap.Val as AccountNumber,
				   ua.Alias,
				   ua.ClientID,
				   ua.Email,
				   ua.SendEmails
			from Account a
			left join AccountProperty ap on a.AccountID = ap.AccountID
			left join UserAgent ua on a.UserAgentID = ua.UserAgentID
			where a.AccountID = ?', [$accountId], [\PDO::PARAM_INT])->fetch(\PDO::FETCH_ASSOC);
        }

        return !empty($data) ? $data : null;
    }

    private function createAccount(Usr $user, Provider $provider, $login, $useragent = 0, $emailSource = null)
    {
        $newAcc = $this->accountFactory->create();

        if (!empty($login)) {
            $newAcc->setLogin($login);
        }
        $newAcc->setProviderid($provider);

        if (!empty($login2)) {
            $newAcc->setLogin2($login2);
        }
        $newAcc->setUser($user);

        if ($useragent > 0) {
            $newAcc->setUserAgent($this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->findOneBy([
                'useragentid' => $useragent,
            ]));
        }
        $newAcc->setPass('');
        $newAcc->setState(ACCOUNT_PENDING);

        if ($emailSource) {
            $newAcc->setSourceEmail($emailSource);
        }

        try {
            $this->em->persist($newAcc);
            $this->em->flush($newAcc);
            $this->logger->info("New pending account: {$newAcc->getAccountid()} {$newAcc->getLogin()} {$newAcc->getSourceEmail()}");

            return [
                "ID" => $newAcc->getAccountid(),
                "UserID" => $user->getUserid(),
                "UserAgentID" => $useragent,
                "Login" => $login,
                "AccountNumber" => null,
                "Balance" => null,
                "Pending" => true,
            ];
        } catch (\Exception $e) {
            $this->logger->debug('Error creating account');

            return null;
        }
    }

    private function sendBalanceUpdated($account, Usr $user)
    {
        $accountId = $account['ID'];

        if (!empty($account['UserAgentID']) && empty($account['ClientID']) && !empty($account['Email'])) {
            if (empty($account['SendEmails'])) {
                $this->logger->debug('dont send balance updated notification due to unckecked box');

                return;
            }
            $fm = $this->userAgentRep->find($account['UserAgentID']);
        }
        $account = $this->getUpdatedAccount($accountId, $user);

        if (!$account) {
            return;
        }

        $template = new StatementParsed($fm ?? $user);
        $template->account = $account;
        $opt = new Options(ADKIND_EMAIL, $user, RewardsActivity::getEmailKind());
        $template->advt = $this->adManager->getAdvt($opt);

        if (!isset($fm)) {
            $template->hasMailbox = $this->hasMailboxes($user);
        }

        $message = $this->mailer->getMessageByTemplate($template);
        $this->logger->info('mailing balance updated to ' . $template->getEmail());
        $this->mailer->send($message);
    }

    private function getUpdatedAccount($accountId, Usr $user)
    {
        $query = $this->connection->executeQuery(
            'select UpdateDate from AccountBalance where AccountID = ? order by AccountBalanceID desc limit 2',
            [$accountId],
            [\PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($query)) {
            return null;
        }

        $dates = [];

        foreach ($query as $item) {
            $date = date_create($item['UpdateDate']);

            if ($date) {
                $dates[] = $date;
            }
        }

        if (sizeof($dates) != 2) {
            return null;
        }

        $accounts = $this->recentlyList->getAccounts($user, null, $dates[1], $dates[0], " AND a.AccountID = $accountId");

        if (!sizeof($accounts)) {
            return null;
        }

        return $accounts[0];
    }

    private function forwardEmail(EmailOptions $info, Usr $user, ?Useragent $userAgent, $account)
    {
        if ($info->silent || !$info->forwardToUser || empty($info->parser->getCleanFrom())) {
            return;
        }

        // 22964#note-52
        if ($info->forwardedWithFilter || stripos($info->parser->getCleanTo(), '@email.awardwallet.com') !== false) {
            return;
        }
        $to = strtolower(!empty($userAgent) && !empty($userAgent->getEmail()) ? $userAgent->getEmail() : $user->getEmail());
        $check = [$to];

        if (isset($account) && !empty($account['UserAgentID']) && empty($account['ClientID']) && !empty($account['Email'])) {
            $to = strtolower($account['Email']);
            $check[] = $to;
            $this->logger->info('changing \'to\' address to agent\'s email', ['to' => $to]);
        }

        if (in_array(strtolower(Util::clearHeader($info->parser->getHeader('from'))), $check)) {
            $this->logger->info("message from user, do not forward");
        } elseif (in_array(strtolower($info->parser->getCleanTo()), $check)) {
            $this->logger->info("'To' header matches user email, assume email was autoforwarded and do not forward to user");
        } else {
            $this->logger->info("forwarding to " . $to);

            if ($user->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS && !isset($to)) {
                $this->logger->info("this is business and useragent is empty, skip");

                return;
            }

            $headers = Util::filterHeaders($info->parser->getRawHeaders());
            // disabled copying 12nov24
            // $headers[] = "Bcc: notifications@awardwallet.com";
            $from = Util::clearHeader($info->parser->getHeader('From'));
            $headers[] = "From: " . str_ireplace($from, DO_NOT_REPLY_EMAIL, $info->parser->getHeader('From'));

            if ($info->parser->getHeader('Reply-To') == '') {
                $headers[] = "Reply-To: " . $info->parser->getHeader('From');
            }
            $headers = implode("\n", $headers);
            $result = mailTo($to, $info->parser->getSubject(), $info->parser->getBodyStr(), $headers);
            $this->logger->info("mailed to {$to}, {$info->parser->getSubject()}, headers: " . json_encode($headers) . ", body length: " . strlen($info->parser->getBodyStr()) . ", result: " . json_encode($result));
        }
    }

    private function sendParseError(EmailOptions $info, Usr $user, ?Useragent $ua)
    {
        if (
            !$info->forwardToUser
            || $info->silent
            || !$this->shouldSend($info->parser->getCleanFrom(), $info->parser->getSubject(), $user)
            || $info->source->getSource() !== ParsedEmailSource::SOURCE_PLANS
            || empty($info->source->getUserEmail())
        ) {
            return;
        }

        $this->logger->info("notifying about parse error to " . $user->getEmail());

        if ($user->isBusiness()) {
            $this->logger->debug("this is business, don't know how to notify them, skip");

            return;
        }
        $to = !empty($ua) && !empty($ua->getEmail()) ? $ua : $user;
        $template = new ProblemRecognizingSubmission($to);
        $template->fromEmail = $info->parser->getCleanFrom();
        $template->toEmail = $info->source->getUserEmail();
        $template->subject = $info->parser->getSubject();

        if ($to instanceof Usr) {
            $template->hasMailbox = $this->hasMailboxes($to);
        }
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
    }

    private function shouldSend($from, $subject, Usr $user)
    {
        if (empty($from) || empty($subject)) {
            return false;
        }
        $black = [
            "@awardwallet.com",
            "@awrdwllt.com",
            "@cardcash.com", // spams too much and users seem to not register there themselves
        ];

        foreach ($black as $s) {
            if (stripos($from, $s) !== false) {
                $this->logger->info("$s from address, do not send notification");

                return false;
            }
        }
        $black2 = [
            ['@veloxvisa.com', 'jbianco'],
        ]; // custom

        foreach ($black2 as [$addr, $login]) {
            if (stripos($from, $addr) !== false && $user->getLogin() === $login) {
                return false;
            }
        }

        return true;
    }

    // only works with '^abcde' and 'abcde$'
    private function clearPartialRegexp($str)
    {
        if (stripos($str, '^') === 0) {
            return substr($str, 1) . "****";
        }

        if (stripos($str, '$') === strlen($str) - 1) {
            return "****" . substr($str, 0, strlen($str) - 1);
        }

        return $str;
    }

    private function compareNames($name1, $name2)
    {
        if (strcasecmp(trim($name1), trim($name2)) === 0) {
            return true;
        }
        $name1 = explode(" ", $name1);
        $name2 = explode(" ", $name2);
        $similarity = 0;

        foreach ($name1 as $word) {
            if (in_array($word, $name2)) {
                $similarity++;
            }
        }

        return $similarity;
    }

    private function assignRecipientParts(EmailOptions $info, ?Owner $owner)
    {
        $parts = $info->recipientParts;

        // by owner
        if ($owner !== null) {
            $parts['UserLogin'] = $owner->getUser()->getLogin();

            if ($owner->getFamilyMember() !== null && $owner->getFamilyMember()->getAlias() !== null) {
                $parts['Alias'] = $owner->getFamilyMember()->getAlias();
            }
        }

        // user login
        if (isset($parts[0])) {
            if ($usr = $this->userRep->findOneByLogin($parts[0])) {
                $parts["UserLogin"] = $parts[0];
            }
        }

        // alias or account login
        if (isset($parts[1])) {
            if (isset($usr)) {
                if ($this->userAgentRep->findOneBy(["agentid" => $usr, "alias" => $parts[1]])) {
                    $parts["Alias"] = $parts[1];
                }
            }

            if (!isset($parts["Alias"])) {
                $parts["AccountLogin"] = $parts[1];
            }
        }

        // account login
        if (isset($parts[2])) {
            $parts["AccountLogin"] = $parts[2];
        }
        $info->recipientParts = $parts;
        $this->logger->debug("parseAddress " . $info->parser->getCleanTo() . " -> " . var_export($parts, true));
    }

    private function checkProtonConfirmation(ParseEmailResponse $data, EmailOptions $options, Usr $user)
    {
        if (stripos($options->parser->getSubject(), 'Confirm forwarding request') === false) {
            return false;
        }

        if (stripos($options->parser->getCleanFrom(), 'no-reply@mail.proton.me') === false) {
            return false;
        }
        $key = $body = null;
        $success = false;
        $options->refreshEmail($data->email);
        $html = $options->parser->getHTMLBody();

        if (preg_match("/title=\"Accept forwarding on Proton\" href=\"https:\/\/account.proton.me\/email-forwarding\/accept#([^\"]+)\"/i", $html, $m)) {
            $key = $m[1];
        }

        if (isset($key)) {
            $url = 'https://account.proton.me/api/mail/v4/forwardings/external/' . $key;
            $headers = [
                'Accept' => 'application/vnd.protonmail.v1+json',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'X-Pm-Appversion' => 'web-account@5.0.153.0',
                'X-Pm-Locale' => 'en_US',
            ];
            $response = $this->httpDriver->request(new \HttpDriverRequest(
                $url,
                'GET',
                null,
                $headers,
                30
            ));
            $body = $response->body;
            $bodeDecoded = @json_decode($body, true);

            if ($bodeDecoded && isset($bodeDecoded['Code']) && $bodeDecoded['Code'] === 1000) {
                $success = true;
            }
        }
        $this->logger->info('proton email forwarding stat', [
            'userId' => $user->getId(),
            'key' => $key,
            'responseBody' => Strings::cutInMiddle($body, 200),
            'success' => $success,
        ]);

        return $success;
    }

    private function checkGmailConfirmation(ParseEmailResponse $data, EmailOptions $options, Usr $user)
    {
        if (stripos($options->parser->getSubject(), 'Forwarding Confirmation') === false || stripos($options->parser->getSubject(), 'Receive Mail from') === false) {
            return false;
        }

        if (stripos($options->parser->getCleanFrom(), 'forwarding-noreply@google.com') === false) {
            return false;
        }
        $options->refreshEmail($data->email);
        $parser = $options->parser;
        $this->logger->debug("Gmail confirmation email");
        $body = $parser->getHTMLBody();

        if (preg_match("/(?<link>https.+mail(-settings)?.google.com\/.+\/vf.+)/i", $body, $matches)) {
            $link = $matches['link'];
        }

        if (preg_match("/Confirmation code: (\d+)/", $body, $matches)) {
            $confirmationCode = $matches[1];
        } else {
            $confirmationCode = null;
        }

        if (isset($link)) {
            $http = new \HttpBrowser("none", new \CurlDriver());
            $http->LogHeaders = true;
            $http->OnLog = function ($message) {
                $this->logger->debug($message);
            };
            $this->logger->debug("loading confirmation link: " . $link);
            $http->GetURL($link);
            $confirms = [
                'Please confirm mail forwarding',
                'Please confirm forwarding mail',
                '메일을 전달합니다',
                'Confirme o encaminhamento de e-mails de',
            ];
            $confirmRegex = '/' . implode('|', $confirms) . '/ims';
            $successes = [
                'may now forward',
                '에 메일을 전달할 수 있습니다',
                'pode encaminhar e-mails para',
            ];

            if (preg_match($confirmRegex, $http->Response['body'])) {
                $this->logger->debug("confirming, sending form");
                $http->setDefaultHeader('Upgrade-Insecure-Requests', '1');

                if ($http->PostURL($http->currentUrl(), [])) {
                    foreach ($successes as $success) {
                        if (stripos($http->Response['body'], $success) !== false) {
                            $this->logger->info('gmail forwarding confirmed');
                            $this->sendGmailForwardingNotification($user, $confirmationCode);

                            return true;
                        }
                    }
                    $this->logger->error("confirmation message not found", ['info' => $http->Response['body']]);
                } else {
                    $this->logger->error("confirmation form failed", ['info' => $http->Response['body']]);

                    if ($http->FindPreg("#Temporary Error#ims")) {
                        return true;
                    }
                }
            } else {
                $this->logger->error("Confirmation error", ['info' => $http->Response['body']]);
            }
        } else {
            $this->logger->error("Confirmation link or code not found");
        }

        return false;
    }

    private function sendGmailForwardingNotification(Usr $user, $gmailNotificationCode)
    {
        $this->logger->debug("gmail forwarding notification: " . $user->getEmail());

        if ($user->isBusiness()) {
            $this->logger->info('business user, dont know how to notify them');
        }

        $template = new GmailForwarding($user);
        $template->code = $gmailNotificationCode;

        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
    }

    private function locateOwnerByRecipientParts(array $recipientParts): ?Owner
    {
        $user = null;

        if (isset($recipientParts["UserLogin"])) {
            $user = $this->userRep->findOneByLogin($recipientParts["UserLogin"]);
        }

        if ($user === null) {
            return null;
        }

        $userAgent = null;

        if (isset($recipientParts['Alias'])) {
            $userAgent = $user->findFamilyMemberByAlias($recipientParts['Alias']);
        }

        return new Owner($user, $userAgent);
    }

    private function hasMailboxes(Usr $user)
    {
        try {
            return count($this->scannerApi->listMailboxes(["user_" . $user->getUserid()])) > 0;
        } catch (\Exception $e) {
            // do not show offer
            return true;
        }
    }

    private function processAaAwardRedemption(ParseEmailResponse $data)
    {
        $mailboxId = $data->metadata->mailboxId;
        $matching = $this->memcached->get($this->getAaCacheKey($mailboxId));
        $this->logger->info("Mailbox: {$mailboxId}. CacheInfo: " . var_export($matching, true));

        if ($matching && !$matching['blocked']) {
            $travelerIds = [];

            foreach ($data->awardRedemption as $ar) {
                $receivedTimeAr = strtotime($data->metadata->receivedDateTime);
                $receivedTimeRes = $matching['receivedDateTime'];

                $isSameTime = ($receivedTimeRes <= $receivedTimeAr
                    && $receivedTimeAr <= ($receivedTimeRes + 900));

                if ($isSameTime) {
                    $travelerId = array_search(
                        Util::normalizeTravelerString($ar->recipient),
                        $matching['trip']['travelers']
                    );

                    if ($travelerId !== false) {
                        $matching['trip']['milesRedeemed'] += $ar->milesRedeemed;
                        $travelerIds[] = $travelerId;
                        $this->logger->info("Matching {$ar->milesRedeemed} miles redeemed for {$matching['trip']['travelers'][$travelerId]}. Mailbox: {$mailboxId}.");
                    }
                }
            }

            if (count($travelerIds) > 0) {
                --$matching['waitRedemptions'];
            }

            if ($matching['waitRedemptions'] > 0) {
                $this->memcached->set(
                    $this->getAaCacheKey($mailboxId),
                    $matching,
                    $matching['expiry'] - time(),
                );
                $this->logger->info("Need to wait {$matching['waitRedemptions']} award redemptions. Mailbox: {$mailboxId}.");

                return true;
            }

            if ($matching['waitRedemptions'] == 0) {
                /** @var Trip $trip */
                $trip = $this->tripRep->find($matching['trip']['id']);
                $trip->setPricingInfo(
                    $trip->getPricingInfo()->withSpentAwards(
                        $matching['trip']['milesRedeemed']
                    )
                );
                $this->em->flush();

                $this->memcached->set($this->getAaCacheKey($mailboxId), null);

                $this->logger->info("All award redemptions successful processed. SpentAwards: {$matching['trip']['milesRedeemed']}. Mailbox: {$mailboxId}.");

                return true;
            }

            $this->logger->info("Mailbox: {$mailboxId}. CacheInfo: " . var_export($matching, true));
        }

        return false;
    }

    private function getAaCacheKey(string $mailboxId): string
    {
        return 'aa_matching_reservation_' . $mailboxId;
    }

    private function getLockerKey(int $user, string $provider): string
    {
        return sprintf('cb_process_lock_%d_%s', $user, $provider);
    }

    private function lock(int $user, string $provider): void
    {
        $this->memcached->set($this->getLockerKey($user, $provider), '1', 3);
    }

    private function throttle(int $user, string $provider): void
    {
        $timer = 0;

        while ($this->memcached->get($this->getLockerKey($user, $provider))) {
            if ($timer == 0) {
                $this->logger->info('callback processor locked by another email');
            }
            $timer++;

            if ($timer > 10) {
                $this->logger->info('callback processor locked for more than 10 seconds, releasing');

                return;
            }
            sleep(1);
        }
    }
}
