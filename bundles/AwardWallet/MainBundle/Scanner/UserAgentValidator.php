<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserAgentValidator
{
    /**
     * @var UseragentRepository
     */
    private $useragentRepository;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(UseragentRepository $useragentRepository, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->useragentRepository = $useragentRepository;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function checkAgentId(?string $agentId, string $exceptionClass = AccessDeniedHttpException::class): ?int
    {
        if (empty($agentId) || ('my' === $agentId)) {
            $result = null;
        } else {
            $result = (int) $agentId;
        }

        if ($result !== null) {
            $agent = $this->useragentRepository->find($result);

            if ($agent === null || !$this->authorizationChecker->isGranted('EDIT_TIMELINE', $agent)) {
                throw new $exceptionClass();
            }
        }

        return $result;
    }
}
