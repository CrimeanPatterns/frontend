<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderpropertyRepository;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StatementSaver
{
    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private AccountRepository $ar;

    private ProviderpropertyRepository $ppr;

    private BalanceProcessor $balanceProcessor;

    private EventDispatcherInterface $eventDispatcher;

    private AccountFactory $accountFactory;
    private DoctrineRetryHelper $doctrineRetryHelper;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        AccountRepository $ar,
        ProviderpropertyRepository $providerPropertyRepository,
        BalanceProcessor $balanceProcessor,
        EventDispatcherInterface $eventDispatcher,
        AccountFactory $accountFactory,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->ar = $ar;
        $this->ppr = $providerPropertyRepository;
        $this->balanceProcessor = $balanceProcessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->accountFactory = $accountFactory;
        $this->doctrineRetryHelper = $doctrineRetryHelper;
    }

    public function createDiscoveredAccount(Owner $owner, Provider $provider, LoyaltyAccount $data, $email)
    {
        if ('aa' === $provider->getCode()) {
            return null;
        }
        $this->logger->info('creating discovered account');
        $acc = $this->accountFactory->create();
        $acc->setProviderid($provider);

        if ($provider->getId() === Provider::AA_ID) {
            $this->logger->info('setting aa password storage as local, we could not save password to db by contract');
            $acc->setSavepassword(SAVE_PASSWORD_LOCALLY);
        }
        $acc->setOwner($owner);

        if ($data->login) {
            $acc->setLogin($this->maskValue($data->login, $data->loginMask));
        } else {
            $acc->setLogin('');
        }
        $acc->setPass('');
        $acc->setUpdatedate(null);
        $acc->setState(ACCOUNT_PENDING);
        $acc->setSourceEmail($email);
        $this->em->persist($acc);
        $this->em->flush();

        return $acc;
    }

    public function save(Account $acc, LoyaltyAccount $data, ?\DateTime $sourceDate): bool
    {
        if ($sourceDate && $sourceDate > (new \DateTime())) {
            // Date in email `Received` header is sometimes ahead
            $sourceDate = null;
        }
        $balanceDate = $data->balanceDate ? new \DateTime($data->balanceDate) : $sourceDate;

        if ($acc->getProviderid()) {
            if (!StatementHelper::isEmailProvider($acc->getProviderid())
                && !in_array($acc->getState(), [ACCOUNT_PENDING, ACCOUNT_IGNORED])
                && (empty($acc->getUpdatedate()) || $acc->getUpdatedate()->diff(new \DateTime(), true)->days < 30)) {
                $this->logger->info('account is not updatable through emails');

                return false;
            }
        }

        if (isset($balanceDate) && !empty($acc->getUpdatedate()) && $acc->getUpdatedate() > $balanceDate) {
            $this->logger->info(sprintf('statement is older (%s) than last update(%s)', $balanceDate->format('Y-m-d H:i'), $acc->getUpdatedate()->format('Y-m-d H:i')));

            return false;
        }

        $acc->setCheckedby(Account::CHECKED_BY_EMAIL);

        if (null !== $data->balance) {
            if ($this->balanceProcessor->saveAccountBalance($acc, $data->balance)) {
                $this->eventDispatcher->dispatch(new AccountBalanceChangedEvent($acc));
            }
            $acc->setUpdatedate($balanceDate ?? new \DateTime());
            $acc->setSuccesscheckdate($balanceDate ?? new \DateTime());
        }

        if ($acc->getProviderid() && StatementHelper::isCeasedProvider($acc->getProviderid())) {
            $acc->setQueuedate(null);
        }

        if ($data->expirationDate) {
            $expDate = new \DateTime($data->expirationDate);

            if (null === $balanceDate || $expDate > $balanceDate) {
                $acc->setExpirationdate(new \DateTime($data->expirationDate));
            }
        }

        if ($data->login && (!$acc->getLogin() || !$data->loginMask && $acc->getState() === ACCOUNT_PENDING)) {
            $acc->setLogin($this->maskValue($data->login, $data->loginMask));
        }

        if (!empty($data->properties)) {
            foreach ($data->properties as $property) {
                $this->saveProperty($acc, $property->code, $property->value);

                if (strcasecmp('status', $property->code) === 0 && $acc->getProviderid() === null && !empty($property->value)) {
                    $acc->setCustomEliteLevel($property->value);
                }
            }
        }
        $number = $acc->getAccountPropertyByKind(PROPERTY_KIND_NUMBER);

        if ($data->number && (empty($number) || !$data->numberMask && $acc->getState() === ACCOUNT_PENDING)) {
            $this->saveNumber($acc, $data->number, $data->numberMask);
        }

        if ($data->login2 && empty($acc->getLogin2()) && $acc->getState() === ACCOUNT_PENDING) {
            $acc->setLogin2($data->login2);
        }

        if ($acc->getProviderid() !== null
            && $acc->getProviderid()->getId() === Provider::AA_ID
            && $acc->getSavepassword() === SAVE_PASSWORD_DATABASE
            && $acc->getUseragents()->count() === 0) {
            $this->logger->info('setting aa password storage as local, we could not save password to db by contract');
            $acc->setSavepassword(SAVE_PASSWORD_LOCALLY);
        }

        $this->doctrineRetryHelper->execute(function () {
            $this->em->flush();
        });
        $this->eventDispatcher->dispatch(new AccountUpdateEvent($acc, AccountUpdateEvent::SOURCE_EMAIL));
        $this->logger->info('saved account ' . $acc->getAccountid());

        return true;
    }

    public function saveEmailExclusive(Account $account, LoyaltyAccount $data): bool
    {
        // refs #22644 Lifetime Base Points is not shown in web, but is present in emails
        if (is_array($data->properties) && $account->getProviderid() && $account->getProviderid()->getCode() === 'goldpassport') {
            foreach ($data->properties as $row) {
                if ($row->code === 'LifetimeBasePoints' && strlen($row->value) > 0) {
                    $this->logger->info('saving Lifetime Points for hyatt');
                    $this->saveProperty($account, $row->code, $row->value);
                    $this->em->flush();

                    return true;
                }
            }
        }

        return false;
    }

    private function saveProperty(Account $acc, $code, $value)
    {
        foreach ($acc->getProperties() as $p) {
            if ($p->getProviderpropertyid()->getCode() === $code) {
                $p->setVal($value);
                $saved = true;
            }
        }

        if (!isset($saved) && $acc->getProviderid() && ($pp = $this->findProviderProperty($acc->getProviderid(), $code))) {
            $ap = new Accountproperty();
            $ap->setProviderpropertyid($pp);
            $ap->setAccountid($acc);
            $ap->setVal($value);
            $this->em->persist($ap);
        }
    }

    private function saveNumber(Account $acc, $val, $mask)
    {
        if (!$acc->getProviderid()) {
            return;
        }
        /** @var Accountproperty $number */
        $number = $this->findAccountProperty($acc, PROPERTY_KIND_NUMBER);
        $npp = $this->findProviderProperty($acc->getProviderid(), null, PROPERTY_KIND_NUMBER);

        if (!$npp) {
            return;
        }
        $toWrite = $this->maskValue($val, $mask);

        if (!$number) {
            $number = new Accountproperty();
            $number->setAccountid($acc);
            $number->setProviderpropertyid($npp);
            $this->em->persist($number);

            if (!empty($mask) && !empty($acc->getLogin()) && StatementHelper::matchMaskedField(strtolower($val), $mask, strtolower($acc->getLogin()))) {
                $toWrite = $acc->getLogin();
            }
        }
        $number->setVal($toWrite);
    }

    private function findProviderProperty(Provider $provider, $code, $kind = null): ?Providerproperty
    {
        if (null !== $code) {
            return $this->ppr->findOneBy(['providerid' => $provider, 'code' => $code]);
        }

        if (null !== $kind) {
            return $this->ppr->findOneBy(['providerid' => $provider, 'kind' => $kind]);
        }

        return null;
    }

    private function findAccountProperty(Account $account, $kind): ?Accountproperty
    {
        foreach ($account->getProperties() as $property) {
            if ($property->getProviderpropertyid()->getKind() === $kind) {
                return $property;
            }
        }

        return null;
    }

    private function maskValue($value, $mask)
    {
        if (strlen($value) == 0) {
            return $value;
        }

        switch ($mask) {
            case 'left':
                $value = '****' . $value;

                break;

            case 'right':
                $value .= '****';

                break;

            case 'center':
                $parts = explode('*', preg_replace('/[*]+/', '*', $value));

                if (count($parts) === 2) {
                    $value = $parts[0] . '**' . $parts[1];
                }
        }

        return $value;
    }
}
