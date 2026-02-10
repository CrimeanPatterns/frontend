<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Event\AccountFormSavedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DiscoveredAccountMigrator
{
    /** @var AccountRepository */
    private $ar;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->ar = $em->getRepository(Account::class);
        $this->logger = $logger;
    }

    public function onSave(AccountFormSavedEvent $event)
    {
        /** @var Account $account */
        if ($account = $this->ar->find($event->getAccountId())) {
            $this->migrateDoublesOf($account);
        }
    }

    public function migrateDoublesOf(Account $acc)
    {
        if (!$acc->getProviderid()) {
            return;
        }
        $list = $this->ar->findBy([
            'user' => $acc->getUser(),
            'providerid' => $acc->getProviderid(),
            'state' => ACCOUNT_PENDING,
        ]);
        $source = null;
        $delete = [];
        $cnt = 0;

        /** @var Account $discovered */
        foreach ($list as $discovered) {
            if ($acc->getId() === $discovered->getId()) {
                continue;
            }
            $delete[] = $discovered;

            continue;
            /* 2020-12-24 decided to remove all discovered accounts w/o migrating data

            $match = $this->match($acc, $discovered);
            if (true === $match && null === $source) {
                $source = $discovered;
            }
            if (true === $match || null === $match) {
                $delete[] = $discovered;
            }
            */
        }

        if (null !== $source) {
            $this->migrate($source, $acc);
            $this->logger->info('Migrated data from discovered account');
        }

        foreach ($delete as $a) {
            $this->em->remove($a);
            $cnt++;
        }
        $this->em->flush();

        if ($cnt > 0) {
            $this->logger->info('Deleted ' . $cnt . ' discovered accounts');
        }
    }

    // true - strict match, false - strict mismatch, null - soft match
    /*
    private function match(Account $active, Account $discovered): ?bool
    {
        $strict = false;
        if ($active->getLogin() && $discovered->getLogin()) {
            if ($this->matchMaskedStrings($active->getLogin(), $discovered->getLogin()))
                return true;
            $strict = true;
        }
        $na = $active->getAccountPropertyByKind(PROPERTY_KIND_NUMBER);
        $nd = $discovered->getAccountPropertyByKind(PROPERTY_KIND_NUMBER);
        if ($na && $nd) {
            if ($this->matchMaskedStrings($na, $nd))
                return true;
            $strict = true;
        }
        if ($active->getLogin() && $nd) {
            if ($this->matchMaskedStrings($active->getLogin(), $nd))
                return true;
        }
        if ($na && $discovered->getLogin()) {
            if ($this->matchMaskedStrings($na, $discovered->getLogin()))
                return true;
        }
        return $strict ? false : null;
    }

    private function matchMaskedStrings(string $active, string $discovered): bool
    {
        if (preg_match('/^[*]+([^*]+)$/', $discovered, $m) > 0) {
            $regexp = '/' . preg_quote($m[1]) . '$/i';
        }
        if (preg_match('/^([^*]+)[*]+$/', $discovered, $m) > 0) {
            $regexp = '/^' . preg_quote($m[1]) . '/i';
        }
        if (preg_match('/^[^*]+[*][*][^*]+$/', $discovered) > 0) {
            list($left, $right) = explode('**', $discovered) + ['', ''];
            if (strlen($left) > 0 && strlen($right) > 0)
                $regexp = '/^' . preg_quote($left) . '.+' . preg_quote($right) . '$/i';
        }
        if (isset($regexp))
            return preg_match($regexp, $active) > 0;
        else
            return strcasecmp($active, $discovered) === 0;
    }
*/

    private function migrate(Account $source, Account $target)
    {
        if (null === $target->getBalance()) {
            $target->setBalance($source->getBalance());
        }
        $present = [];

        foreach ($target->getProperties() as $property) {
            if (null !== $property->getVal()) {
                $present[] = $property->getProviderpropertyid()->getCode();
            }
        }

        foreach ($source->getProperties() as $property) {
            if (!in_array($property->getProviderpropertyid()->getCode(), $present) && null !== $property->getVal()) {
                $new = new Accountproperty();
                $new->setAccountid($target);
                $new->setProviderpropertyid($property->getProviderpropertyid());
                $new->setVal($property->getVal());
                $this->em->persist($new);
            }
        }
    }
}
