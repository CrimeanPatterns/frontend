<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail;

use AwardWallet\MainBundle\Entity\OneTimeCode;
use AwardWallet\MainBundle\Event\OneTimeCodeEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\Otc;
use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\AbstractStep;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OneTimeCodeByEmailStep extends AbstractStep
{
    public const ID = 'one_time_code_by_email';

    private const CODE_MAX_AGE = 60 * 10;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Geo
     */
    protected $globalGeo;
    /**
     * @var Mailer
     */
    protected $mailer;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var SupportChecker
     */
    private $supportChecker;
    /**
     * @var StorageOperations
     */
    private $storageOperations;

    public function __construct(
        LoggerInterface $logger,
        Geo $globalGeo,
        StorageOperations $storageOperations,
        Mailer $mailer,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        SupportChecker $supportChecker
    ) {
        $this->logger = $logger;
        $this->globalGeo = $globalGeo;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->supportChecker = $supportChecker;
        $this->storageOperations = $storageOperations;
    }

    protected function supports(Credentials $credentials): bool
    {
        return $this->supportChecker->supports(
            $credentials,
            $this->getLogContext($credentials)
        );
    }

    protected function doCheck(Credentials $credentials): void
    {
        $user = $credentials->getUser();
        $request = $credentials->getRequest();
        $lastIp = $user->getLastlogonip();

        if (empty($lastIp)) {
            $lastIp = $user->getRegistrationip();
        }

        $codes = $this->storageOperations->findUserCodes($user);

        if (empty($codes) || ($codes[0]->getAge() > self::CODE_MAX_AGE)) {
            $last = $this->globalGeo->getLocationByIp($lastIp);
            $current = $this->globalGeo->getLocationByIp($request->getClientIp());
            $code = new OneTimeCode();
            $code->setUser($user);
            $this->storageOperations->saveCode($code);
            array_unshift($codes, $code);
            $template = new Otc($user);
            $template->code = $code;
            $template->lastIp = $last['ip'];
            $template->lastLocation = $this->globalGeo->getLocationName($last);
            $template->currentIp = $current['ip'];
            $template->currentLocation = $this->globalGeo->getLocationName($current);
            $message = $this->mailer->getMessageByTemplate($template);

            // keep last 10 codes
            $this->storageOperations->saveLastCodes($codes, 10);
            $this->mailer->send([$message], [
                Mailer::OPTION_SKIP_DONOTSEND => true,
            ]);
            $this->eventDispatcher->dispatch(
                new OneTimeCodeEvent($user, $code->getCode()),
                OneTimeCodeEvent::NAME
            );
        }

        $presentedCode = $credentials->getStepData()->getOtcEmailCode();

        if (StringUtils::isNotEmpty($presentedCode)) {
            $code = $this->storageOperations->findUserCode($user, $presentedCode);

            if ($code) {
                $this->logger->info('Provided email-OTC is valid', $this->getLogContext($credentials));
                $this->storageOperations->deleteUserCodes($user);

                return;
            }

            $this->logger->warning('Provided email-OTC is invalid', $this->getLogContext($credentials));
            $this->throwErrorException($this->translator->trans(/** @Desc("The access code you've entered is not valid, please try again") */ 'error.auth.email.invalid-code'));
        } else {
            $this->logger->warning('Email-OTC is not provided', $this->getLogContext($credentials));
            $this->throwRequiredException($this->translator->trans(
                /** @Desc("We've noticed that you are logging in from a new location, in order to protect your account we've sent an email to the address you have with us on file. Please check your email and enter the one time access code from that email into the field below") */
                'error.auth.two-factor.email-code-required'
            ));
        }
    }
}
