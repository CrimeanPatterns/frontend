<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Usr;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class InviteLocker implements TranslationContainerInterface
{
    public $message;
    /**
     * @var AntiBruteforceLockerService
     */
    private $ipLocker;

    /**
     * @var AntiBruteforceLockerService
     */
    private $loginLocker;

    /**
     * @var AntiBruteforceLockerService
     */
    private $emailLocker;

    private $requestStack;
    private $memcached;
    private $logger;
    private $email;

    private $suffix = '_invite_locker';
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(\Memcached $memcached, Logger $logger, TokenStorageInterface $tokenStorage, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return $this
     */
    public function init($email)
    {
        $this->email = $email;
        $this->ipLocker = new AntiBruteforceLockerService($this->memcached, $this->getIpPrefix(), 60, 60, 50, 'locked.invite.ip', $this->logger);
        $this->loginLocker = new AntiBruteforceLockerService($this->memcached, $this->getLoginPrefix(), 60, 60, 50, 'locked.invite.login', $this->logger);
        $this->emailLocker = new AntiBruteforceLockerService($this->memcached, $this->getEmailPrefix(), 60, 60, 10, 'locked.invite.email', $this->logger);

        return $this;
    }

    /**
     * @return string|null
     */
    public function check()
    {
        $result = null;

        if ($message = $this->checkIp()) {
            $result = $message;
        }

        if ($message = $this->checkLogin()) {
            $result = $message;
        }

        if ($message = $this->checkEmail()) {
            $result = $message;
        }

        return $result;
    }

    public function checkIp()
    {
        if ($message = $this->ipLocker->checkForLockout($this->getIpPrefix())) {
            return $message;
        }

        return null;
    }

    public function checkLogin()
    {
        if ($message = $this->loginLocker->checkForLockout($this->getLoginPrefix())) {
            return $message;
        }

        return null;
    }

    public function checkEmail()
    {
        if ($message = $this->emailLocker->checkForLockout($this->getEmailPrefix())) {
            return $message;
        }

        return null;
    }

    public function reset()
    {
        $this->ipLocker->unlock($this->getIpPrefix());
        $this->loginLocker->unlock($this->getLoginPrefix());
        $this->emailLocker->unlock($this->getEmailPrefix());
    }

    public function getIpPrefix()
    {
        return $this->requestStack->getCurrentRequest() ? $this->requestStack->getCurrentRequest()->getClientIp() . $this->suffix : '127.0.0.1' . $this->suffix;
    }

    public function getLoginPrefix()
    {
        return (
            $this->tokenStorage->getToken() instanceof TokenInterface
            && $this->tokenStorage->getToken()->getUser() instanceof Usr
        ) ? $this->tokenStorage->getToken()->getUser()->getLogin() . $this->suffix :
                'test' . $this->suffix;
    }

    public function getEmailPrefix()
    {
        return $this->email . $this->suffix;
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('locked.invite.ip'))->setDesc('You have exceeded the maximum number of ivitations from the same ip address.'),
            (new Message('locked.invite.login'))->setDesc('You have exceeded the maximum number of login attempts for the same username.'),
            (new Message('locked.invite.email'))->setDesc('You have exceeded the maximum number of invitations that can be sent to the same email address within 1 hour. Please wait 1 hour before you send another invite to this email.'),
        ];
    }
}
