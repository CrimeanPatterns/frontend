<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LoadUserStep extends AbstractStep
{
    public const ID = 'load_user';
    private const NON_EXISTENT_PREFIX = '_non_existent.';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    public function __construct(LoggerInterface $logger, UserProviderInterface $userProvider)
    {
        $this->logger = $logger;
        $this->userProvider = $userProvider;
    }

    protected function doCheck(Credentials $credentials): void
    {
        if (StringUtils::isEmpty($credentials->getStepData()->getLogin())) {
            $this->logger->warning('Empty login', $this->getLogContext($credentials));

            throw new UsernameNotFoundException('Bad credentials');
        }

        /** @var Usr $user */
        try {
            if ($credentials->isFailed()) {
                // we will emulate search delay, to prevent attacker from detecting user existence by response time
                $searchForLogin = bin2hex(random_bytes(5)) . self::NON_EXISTENT_PREFIX;
            } else {
                $searchForLogin = $credentials->getStepData()->getLogin();
            }
            $user = $this->userProvider->loadUserByUsername($searchForLogin);
            $this->logger->info('User was loaded', $this->getLogContext($credentials, ['userlogin' => $user->getLogin(), 'userid' => $user->getUserid()]));
        } catch (UsernameNotFoundException $e) {
            $this->logger->info('User was not found', $this->getLogContext($credentials, ['userlogin' => $credentials->getStepData()->getLogin()]));
            // create a fake user, for bcrypt calculation, to prevent attacker from detecting user existence by response time
            $user = new Usr();
            $user->setLogin($credentials->getStepData()->getLogin());
            $user->setPass('$2y$13$in8dVOHZ3TRyqVoKz3m81uZKM8g7RuSb8hyOqHsnP0HLy8kHKYDLO'); // will not match the password below
            $credentials->getStepData()->setPassword(self::NON_EXISTENT_PREFIX . bin2hex(random_bytes(5)));
        }

        $credentials->setUser($user);
    }
}
