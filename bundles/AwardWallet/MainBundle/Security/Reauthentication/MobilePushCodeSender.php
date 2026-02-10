<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Common\TimeCommunicator;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobilePushCodeSender implements CodeSenderInterface
{
    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TimeCommunicator
     */
    private $timeCommunicator;

    public function __construct(Sender $sender, TranslatorInterface $translator, TimeCommunicator $timeCommunicator)
    {
        $this->sender = $sender;
        $this->translator = $translator;
        $this->timeCommunicator = $timeCommunicator;
    }

    public function send(AuthenticatedUser $authUser, string $code, Environment $environment): SendReport
    {
        // how to send push notifications to secure devices
        return new SendReport(false);

        $user = $authUser->getEntity();
        $devices = $this->sender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_REAUTH_CODE);

        if (!$devices) {
            return new SendReport(false);
        }

        $message = new Trans(
            /** @Desc("Your Security Code: %code%") */
            'push-notifications.security.security-code.message',
            ['%code%' => $code]
        );

        $title = new Trans(
            /** @Desc("Requires additional verification") */
            'push-notifications.security.security-code.title'
        );

        return new SendReport(
            $this->sender->send(
                new Content(
                    $title,
                    $message,
                    Content::TYPE_REAUTH_CODE,
                    null,
                    (new Options())
                        ->setDeadlineTimestamp($this->timeCommunicator->getCurrentTime() + DateTimeUtils::SECONDS_PER_DAY)
                        ->setPriority(8)
                        ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
                ),
                $devices
            ),
            $this->translator->trans(/** @Desc("mobile device") */ 'recipient.mobile', [], 'messages', $user->getLanguage())
        );
    }
}
