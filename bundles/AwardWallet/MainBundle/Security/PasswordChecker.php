<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 4/12/18
 * Time: 4:07 PM.
 */

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class PasswordChecker
{
    /**
     * @var AntiBruteforceLockerService
     */
    protected $ipLocker;
    /**
     * @var AntiBruteforceLockerService
     */
    protected $loginLocker;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var string
     */
    private $revealMasterPassword;

    public function __construct(
        AntiBruteforceLockerService $ipLocker,
        AntiBruteforceLockerService $loginLocker,
        LoggerInterface $logger,
        EncoderFactoryInterface $encoderFactory,
        EntityManager $entityManager,
        string $revealMasterPassword
    ) {
        $this->ipLocker = $ipLocker;
        $this->loginLocker = $loginLocker;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class);
        $this->encoderFactory = $encoderFactory;
        $this->entityManager = $entityManager;
        $this->revealMasterPassword = $revealMasterPassword;
    }

    public function checkPasswordSafe(Usr $user, string $password, string $clientIp, &$lockerError = null)
    {
        $error = $this->ipLocker->checkForLockout($clientIp, true);

        if (StringUtils::isNotEmpty($error)) {
            $lockerError = $error;

            return false;
        }

        $error = $this->loginLocker->checkForLockout($user->getUsername());

        if (StringUtils::isNotEmpty($error)) {
            $lockerError = $error;

            return false;
        }

        try {
            $this->checkPasswordUnsafe($user, $password);
        } catch (BadCredentialsException $e) {
            $this->ipLocker->checkForLockout($clientIp);

            return false;
        }

        $this->loginLocker->unlock($user->getLogin());

        return true;
    }

    public function checkPasswordUnsafe(Usr $user, string $presentedPassword)
    {
        if ("" === $presentedPassword) {
            $this->logger->warning("empty password", ["UserID" => $user->getUserid(), "login" => $user->getLogin()]);

            throw new BadCredentialsException('Bad credentials');
        }

        if (!$this->encoderFactory->getEncoder($user)->isPasswordValid((string) $user->getPassword(), $presentedPassword, $user->getSalt())) {
            // check old password-format
            $oldPasswordEncoder = new MessageDigestPasswordEncoder('md5', false, 1);

            if ($oldPasswordEncoder->isPasswordValid((string) $user->getPassword(), $presentedPassword, 0)) {
                // store in new format
                $user->setPass($this->encoderFactory->getEncoder($user)->encodePassword($presentedPassword, null));
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->logger->warning("stored password in new format", ["UserID" => $user->getUserid(), "login" => $user->getLogin()]);
            } else {
                $this->logger->warning("invalid password", ["UserID" => $user->getUserid(), "login" => $user->getLogin(), "IsStaff" => $user->hasRole('ROLE_STAFF')]);

                throw new BadCredentialsException('Bad credentials');
            }
        } else {
            $this->logger->info("valid password", ["UserID" => $user->getUserid(), "login" => $user->getLogin()]);
        }
    }
}
