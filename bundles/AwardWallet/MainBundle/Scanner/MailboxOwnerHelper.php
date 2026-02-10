<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Psr\Log\LoggerInterface;

class MailboxOwnerHelper
{
    /**
     * @var UsrRepository
     */
    private $usrRepository;
    /**
     * @var UseragentRepository
     */
    private $useragentRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UsrRepository $usrRepository, UseragentRepository $useragentRepository, LoggerInterface $logger)
    {
        $this->usrRepository = $usrRepository;
        $this->useragentRepository = $useragentRepository;
        $this->logger = $logger;
    }

    public function getOwnerByUserData(?string $userDataStr): Owner
    {
        [$userId, $userAgentId] = $this->getOwnerIdsFromUserData($userDataStr);

        /** @var Usr $user */
        $user = $this->usrRepository->find($userId);

        if ($user === null) {
            throw new \InvalidArgumentException("Missing user: " . $userDataStr);
        }

        /** @var Useragent $userAgent */
        $userAgent = null;

        if ($userAgentId !== null) {
            $userAgent = $this->useragentRepository->find($userAgentId);

            if ($userAgent === null) {
                $this->logger->warning("failed to find userAgent, deleted? userAgentId: $userAgentId");
            }
        }

        return new Owner($user, $userAgent);
    }

    /**
     * @return array [userId, userAgentId/null]
     */
    public function getOwnerIdsFromUserData(?string $userDataStr): array
    {
        $userData = json_decode($userDataStr, true);

        return [$userData['user'], $userData['userAgent'] ?? null];
    }
}
