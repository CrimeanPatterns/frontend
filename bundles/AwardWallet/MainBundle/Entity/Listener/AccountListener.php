<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\CouponRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Repository\ProvidercouponRepository;
use AwardWallet\MainBundle\Service\CapitalcardsHelper;
use AwardWallet\MainBundle\Service\Counter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccountListener
{
    private $localPasswordsManager;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var \AwardWallet\MainBundle\Entity\Repositories\TravelplanRepository
     */
    private $plansRepo;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var Counter */
    private $counter;

    private $validator;

    /**
     * @var CouponRepository
     */
    private $couponRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CapitalcardsHelper
     */
    private $capitalcardsHelper;

    private PasswordEncryptor $passwordEncryptor;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(
        LocalPasswordsManager $localPasswordsManager,
        ManagerRegistry $doctrine,
        EventDispatcherInterface $dispatcher,
        Counter $counter,
        ValidatorInterface $validator,
        ProvidercouponRepository $couponRepository,
        LoggerInterface $logger,
        CapitalcardsHelper $capitalcardsHelper,
        PasswordEncryptor $passwordEncryptor,
        PasswordDecryptor $passwordDecryptor
    ) {
        $this->localPasswordsManager = $localPasswordsManager;
        $this->em = $doctrine->getManager();
        $this->plansRepo = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Travelplan::class);
        $this->dispatcher = $dispatcher;
        $this->counter = $counter;
        $this->validator = $validator;
        $this->couponRepository = $couponRepository;
        $this->logger = $logger;
        $this->capitalcardsHelper = $capitalcardsHelper;
        $this->passwordEncryptor = $passwordEncryptor;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    public function postPersist(Account $account, LifecycleEventArgs $event)
    {
        $account->localPasswordManager = $this->localPasswordsManager;
        $this->saveLocalPassword($account);

        if (
            $account->getProviderid()
            && $account->getProviderid()->getState() == PROVIDER_CHECKING_OFF
        ) {
            $account->setBackgroundCheck(false);
        }
    }

    public function preUpdate(Account $account, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();

        if (sizeof(array_diff_key($changeSet, array_flip(['lastchangedate', 'lastbalance'])))) {
            $account->setModifydate(new \DateTime());
        }
    }

    public function postUpdate(Account $account, LifecycleEventArgs $event)
    {
        $this->saveLocalPassword($account);
        $changeSet = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($account);

        if (isset($changeSet['useragents'])) {
            $userAgents = $account->getUseragents()->getValues();

            /** @var Useragent[] $userAgents */
            foreach ($userAgents as $userAgent) {
                $this->counter->invalidateTotalAccountsCounter($userAgent->getAgentid()->getUserid());
            }
        }

        if (isset($changeSet['user'])) {
            if ($account->getUser()->isBusiness()) {
                $query = $event->getObjectManager()->createQueryBuilder();
                $query->delete(Accountshare::class, 's')
                    ->where($query->expr()->eq('s.accountid', $account->getAccountid()));
                $query->getQuery()->execute();
            }
        }

        if (isset($changeSet['user']) || isset($changeSet['userAgent'])) {
            $this->updateCouponsOwner($account);
            $this->relinkPlans($account);
        }
    }

    public function postLoad(Account $account, LifecycleEventArgs $event)
    {
        // we will use this instance in Account.getPass method
        $account->localPasswordManager = $this->localPasswordsManager;
        $account->setPasswordEncryptor($this->passwordEncryptor);
        $account->setPasswordDecryptor($this->passwordDecryptor);
    }

    public function postRemove(Account $account, LifecycleEventArgs $event)
    {
        /** this one will also be fired on removing user,
         * because SymfonyMysqlConnection::Delete removes entities through em.
         */
        if ($account->getProviderid() !== null && $account->getProviderid()->getCode() === 'capitalcards' && $account->getAuthInfo() !== null) {
            $this->logger->info("revoking capital one oauth on account removal");
            $this->capitalcardsHelper->revokeAuthInfo($account->getAuthInfo());
        }
    }

    private function saveLocalPassword(Account $account)
    {
        if ($account->getSavepassword() == SAVE_PASSWORD_LOCALLY) {
            if (!empty($account->getPass())) {
                $this->localPasswordsManager->setPassword($account->getAccountid(), $account->getPass());
            }
        } else {
            $this->localPasswordsManager->removePassword($account->getAccountid());
        }
    }

    private function updateCouponsOwner(Account $account)
    {
        /** @var Providercoupon[] $coupons */
        $coupons = $this->couponRepository->findBy(['account' => $account]);

        foreach ($coupons as $coupon) {
            $coupon->setOwner($account->getOwner());
        }
    }

    /**
     * change UserID, UserAgentID field of Itineraries when we've changed owner of account
     * from which this itineraries was gathered.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function relinkPlans(Account $account)
    {
        foreach (Itinerary::$table as $table) {
            if ($account->getUserAgent()) {
                $uaCond = "r.userAgent <> " . $account->getUserAgent()->getUseragentid();
                $uaCond .= " OR r.userAgent IS NULL";
            } else {
                $uaCond = "r.userAgent IS NOT NULL";
            }

            $class = Itinerary::getItineraryClass($table);
            $q = $this->em->createQuery("
                SELECT
                    r
                FROM
                    {$class} r
                WHERE
                    r.account = :account
                    AND (r.user <> :user OR ($uaCond))
            ")
                ->setParameter('account', $account)
                ->setParameter('user', $account->getUser())
            ;

            /** @var Itinerary[] $its */
            $its = $q->getResult();

            foreach ($its as $it) {
                if ($it->getOwnerId() !== $account->getOwnerId()) {
                    $this->logger->warning("changing itinerary owner, $table, from {$it->getOwnerId()} to {$account->getOwnerId()}");
                    $it->setUser($account->getUserid());
                    $it->setUserAgent($account->getUseragentid());

                    $errors = $this->validator->validate($it);

                    if (sizeof($errors) == 0) {
                        $this->em->flush($it);
                    } else {
                        $this->em->refresh($it);
                    }
                }
            }
        }
    }
}
