<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\Loyalty\LoyaltyAccount;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use Psr\Log\LoggerInterface;

class StatementMatcher
{
    /** @var AccountRepository */
    private $ar;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(AccountRepository $ar, LoggerInterface $logger)
    {
        $this->ar = $ar;
        $this->logger = $logger;
    }

    public function match(Owner $owner, Provider $provider, LoyaltyAccount $data): MatchReport
    {
        $report = new MatchReport();
        $list = $this->ar->findBy(['user' => $owner->getUser(), 'providerid' => $provider]);
        $report->cnt = 0;
        /** @var Account[] $ownedAccounts */
        $ownedAccounts = [];
        $ownedCnt = 0;

        /** @var Account $acc $acc */
        foreach ($list as $acc) {
            $report->cnt++;

            if ($acc->getState() == ACCOUNT_IGNORED) {
                $this->logger->info('there is an ignored account');

                continue;
            }
            $ownerMatch = $mismatch = false;

            if (null === $owner->getFamilyMember() && null === $acc->getUserAgent()
                || $owner->getFamilyMember() && $acc->getUserAgent() && $owner->getFamilyMember()->getId() === $acc->getUserAgent()->getId()) {
                $ownerMatch = true;
            }
            $number = $acc->getAccountPropertyByKind(PROPERTY_KIND_NUMBER);

            if ($this->matchMaskedField($data->login, $data->loginMask, $acc->getLogin())
                || $this->matchMaskedField($data->number, $data->numberMask, $number)
                || $this->matchMaskedField($data->number, $data->numberMask, $acc->getLogin())
                || $this->matchMaskedField($data->login, $data->loginMask, $number)) {
                if (null !== $report->acc) {
                    $this->logger->info('multiple accounts matched to statement');
                    $report->acc = null;

                    return $report;
                } else {
                    $report->acc = $acc;
                }
            } elseif ($data->login && $acc->getLogin() || $data->number && $number) {
                $mismatch = true;
            }

            if ($ownerMatch) {
                $ownedCnt++;

                if (!$mismatch) {
                    $ownedAccounts[] = $acc;
                }
            }
        }

        if (null === $report->acc && count($ownedAccounts) === 1) {
            if ($ownedAccounts[0]->getState() === ACCOUNT_PENDING) {
                $report->acc = $ownedAccounts[0];
                $this->logger->info('matching single unmatched discovered account');
            } elseif (1 === $report->cnt && empty($data->login) && empty($data->number)) {
                $report->acc = $ownedAccounts[0];
                $this->logger->info('matching single owned account');
            }
        }

        if ($report->acc) {
            $this->logger->info(sprintf('matched account %d (%s)', $report->acc->getAccountid(), $report->acc->getLogin()));
        }

        return $report;
    }

    public function matchCustomAa(Owner $owner, LoyaltyAccount $data): MatchReport
    {
        $report = new MatchReport();
        $list = $this->ar->findBy(['user' => $owner->getUser(), 'providerid' => null]);
        $report->cnt = 0;
        $regexes = AmericanAirlinesAAdvantageDetector::REGEX;

        /** @var Account $acc $acc */
        foreach ($list as $acc) {
            if (empty($acc->getProgramname())) {
                continue;
            }
            $nameMatched = false;

            foreach ($regexes as $regex) {
                if (preg_match($regex, $acc->getProgramname()) > 0) {
                    $nameMatched = true;
                }
            }

            if (!$nameMatched) {
                continue;
            }

            if ((empty($data->login) || $this->matchMaskedField($data->login, $data->loginMask, $acc->getLogin()))
                && (empty($data->number) || $this->matchMaskedField($data->number, $data->numberMask, $acc->getLogin()))) {
                $report->cnt++;
                $report->acc = $acc;
            }
        }

        if ($report->cnt > 1) {
            $report->acc = null;
        }

        if ($report->acc) {
            $this->logger->info(sprintf('matched account %d (%s)', $report->acc->getAccountid(), $report->acc->getLogin()));
        } else {
            $this->logger->info(sprintf('could not match aa custom account: %s, looked at %d', $report->cnt > 0 ? 'too many' : 'none found', count($list)));
        }

        return $report;
    }

    public function checkEmailDates(LoyaltyAccount $data, ?string $receivedDate, Account $account): bool
    {
        $sourceDate = !empty($receivedDate) ? new \DateTime($receivedDate) : null;

        if ($sourceDate && $sourceDate > (new \DateTime())) {
            // Date in email `Received` header is sometimes ahead
            $sourceDate = null;
        }
        $balanceDate = $data->balanceDate ? new \DateTime($data->balanceDate) : $sourceDate;

        if ($balanceDate && $balanceDate > ($now = new \DateTime())) {
            $this->logger->warning(
                sprintf(
                    'Success check date in the future "%s" (now: %s), accountId: %d, balanceDate: %s, sourceDate: %s',
                    $balanceDate->format('Y-m-d H:i:s'),
                    $now->format('Y-m-d H:i:s'),
                    $account->getId(),
                    $data->balanceDate ?? 'null',
                    $sourceDate ? $sourceDate->format('Y-m-d H:i:s') : 'null'
                )
            );

            return false;
        }
        $maxAge = '-30 days';

        if ($account->getProviderid() && $account->getProviderid()->getCode() === 'perksplus') {
            $maxAge = '-90 days';
        }

        if (isset($balanceDate) && $balanceDate < new \DateTime($maxAge)) {
            $this->logger->info(sprintf('statement is too old (%s) to use', $balanceDate->format('Y-m-d H:i')));

            return false;
        }

        return true;
    }

    private function matchMaskedField(?string $data, ?string $mask, ?string $value): bool
    {
        return StatementHelper::matchMaskedField($data, $mask, $value);
    }

    private function isAccEmpty(Account $acc): bool
    {
        return empty($acc->getLogin()) && 0 == $acc->getProperties()->count();
    }
}
