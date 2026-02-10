<?php

namespace AwardWallet\MainBundle\Service\AccountAccessApi;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BusinessFinder
{
    /**
     * @var UsrRepository
     */
    private $usrRepository;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(UsrRepository $usrRepository, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->usrRepository = $usrRepository;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function findByCode(string $refCode): ?Usr
    {
        /** @var Usr $business */
        $business = $this->usrRepository->findOneBy(['refcode' => $refCode]);

        if ($business === null || !$this->authorizationChecker->isGranted('BUSINESS_API_INVITE', $business)) {
            return null;
        }

        return $business;
    }
}
