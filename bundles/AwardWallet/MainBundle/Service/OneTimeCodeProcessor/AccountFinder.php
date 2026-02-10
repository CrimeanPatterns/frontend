<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\Common\OneTimeCode\CommonProvider;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Usr;

class AccountFinder
{
    private OtcCache $cache;

    private AccountRepository $ar;

    public function __construct(OtcCache $cache, AccountRepository $ar)
    {
        $this->cache = $cache;
        $this->ar = $ar;
    }

    /**
     * @return FinderResult account that got otc question error code or possibly will get it
     */
    public function find(Usr $user, string $providerCode): FinderResult
    {
        $qb = $this->ar->createQueryBuilder('a');
        $qb->select('a')
            ->join(Provider::class, 'p', 'WITH', 'a.providerid = p.providerid')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.user', $user->getId()),
                    $qb->expr()->in('p.code', CommonProvider::getCodesList($providerCode))
                )
            );
        $accounts = $qb->getQuery()->execute();
        $result = new FinderResult();

        /** @var Account[] $accounts */
        foreach ($accounts as $account) {
            $check = $this->cache->getCheck($account->getId());
            $up = $this->cache->getUpdate($account->getId());

            if ($check) {
                if (empty($up) || $up < $check) {
                    $result->candidates[] = $account->getId();
                } elseif ($up > $check
                    && $account->getErrorcode() == ACCOUNT_QUESTION
                    && !empty($account->getQuestion())
                    && !empty($account->getProviderid())
                    && \AwardWallet\Common\OneTimeCode\ProviderQuestionAnalyzer::isQuestionOtc($account->getProviderid()->getCode(), $account->getQuestion())) {
                    $result->candidates[] = $account->getId();
                    $result->found = $account;
                }
            }
        }

        return $result;
    }
}
