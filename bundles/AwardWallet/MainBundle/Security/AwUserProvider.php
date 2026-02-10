<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AwUserProvider implements UserProviderInterface
{
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(
        UsrRepository $usrRepository,
        PasswordDecryptor $passwordDecryptor
    ) {
        $this->usrRepository = $usrRepository;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->usrRepository->loadUserByUsername($username);

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }
        /** @var Usr $refreshedUser */
        $refreshedUser = $this->usrRepository->find($user->getUserid());

        // TODO: cache user modifications date in memcache to avoid extra db query
        /** @var Usr $user, $refreshedUser */
        if (
            (!$refreshedUser instanceof UserInterface)
            || ($user->getPassword() !== $refreshedUser->getPassword())
            || ($user->getEmail() !== $refreshedUser->getEmail())
            || ($user->getGoogleAuthSecret() !== $refreshedUser->getGoogleAuthSecret() && $refreshedUser->getGoogleAuthSecret() !== null)
            || ($user->getLogin() !== $refreshedUser->getLogin())
        ) {
            AuthenticationListener::cleanOldSession();

            throw new UsernameNotFoundException('Your password has been changed');
        }

        return $refreshedUser;
    }

    public function supportsClass($class)
    {
        return
            (Usr::class === $class)
            || is_subclass_of($class, Usr::class);
    }
}
