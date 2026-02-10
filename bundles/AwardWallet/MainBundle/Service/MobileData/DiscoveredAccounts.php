<?php

namespace AwardWallet\MainBundle\Service\MobileData;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class DiscoveredAccounts
{
    private AccountRepository $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function getList(Usr $user): array
    {
        return
            it(
                $this->accountRepository
                    ->getPendingsQuery($user, true)
                    ->getQuery()
                    ->execute()
            )
            ->map(function (Account $account) use ($user) {
                return [
                    'id' => $account->getAccountid(),
                    'provider' => $account->getProviderid()->getDisplayname(),
                    'login' => StringUtils::isNotEmpty($account->getLogin()) ?
                            $account->getLogin() :
                            $account->getAccountNumber(),
                    'email' => StringUtils::isNotEmpty($account->getSourceEmail()) ?
                            $account->getSourceEmail() :
                            $user->getFullName(),
                ];
            })
            ->toArray();
    }
}
