<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\ReauthCode;
use AwardWallet\MainBundle\Globals\Geo;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailCodeSender implements CodeSenderInterface
{
    /**
     * @var Geo
     */
    private $geo;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TimeCommunicator
     */
    private $timeCommunicator;

    public function __construct(
        Geo $geo,
        Mailer $mailer,
        TranslatorInterface $translator,
        TimeCommunicator $timeCommunicator
    ) {
        $this->geo = $geo;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->timeCommunicator = $timeCommunicator;
    }

    public function send(AuthenticatedUser $authUser, string $code, Environment $environment): SendReport
    {
        $location = $this->geo->getLocationByIp($environment->getIp());
        $template = new ReauthCode($authUser->getEntity(), $authUser->isBusiness());
        $template->code = $code;
        $template->ip = $location['ip'];
        $template->location = $this->geo->getLocationName($location);
        $message = $this->mailer->getMessageByTemplate($template);

        return new SendReport(
            $this->mailer->send([$message], [
                Mailer::OPTION_SKIP_DONOTSEND => true,
            ]),
            key($message->getTo())
        );
    }
}
