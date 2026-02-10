<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;

class AccountValidator implements ValidatorInterface
{
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function isValid(SourceInterface $source): ?bool
    {
        if (!($source instanceof Account)) {
            return null;
        }

        return $this->accountRepository->find($source->getAccountId()) !== null;
    }
}
