<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;

class ValidMailboxesUpdater
{
    /**
     * @var UserMailboxCounter
     */
    private $userMailboxCounter;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(UserMailboxCounter $userMailboxCounter, UsrRepository $usrRepository)
    {
        $this->userMailboxCounter = $userMailboxCounter;
        $this->usrRepository = $usrRepository;
    }

    public function updateCounter(int $userId): void
    {
        $user = $this->usrRepository->find($userId);

        if ($user === null) {
            return;
        }

        $this->userMailboxCounter->invalidateCache($userId);
        $user->setValidMailboxesCount($this->userMailboxCounter->total($userId, true));
        $this->usrRepository->save($user);
    }
}
