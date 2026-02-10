<?php

namespace AwardWallet\MainBundle\Event\BookingMessage;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Event\BookingMessage;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking\BookingRespond;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Service\Booking\MessageFormatter;
use AwardWallet\MainBundle\Service\SocksMessaging\BookingMessaging;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EmailListener
{
    /**
     * @var BookingMessaging
     */
    private $bookingMessaging;
    /**
     * @var BookingRequestManager
     */
    private $bookingRequestManager;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(
        BookingMessaging $bookingMessaging,
        BookingRequestManager $bookingRequestManager,
        TokenStorageInterface $tokenStorage,
        UsrRepository $usrRepository
    ) {
        $this->bookingMessaging = $bookingMessaging;
        $this->bookingRequestManager = $bookingRequestManager;
        $this->tokenStorage = $tokenStorage;
        $this->usrRepository = $usrRepository;
    }

    public function onBookingNewMessage(BookingMessage\NewEvent $event)
    {
        $abMessage = $event->getAbMessage();
        $extras = $event->getExtras();

        if (isset($extras['emailClass'])) {
            switch ($extras['emailClass']) {
                case BookingRespond::class: $this->sendRespond($abMessage, $extras);

                    break;

                default:
                    throw new \InvalidArgumentException("Unknown email class '{$extras['emailClass']}'");
            }
        }
    }

    private function checkExtras(array $extras, array $requiredFields)
    {
        foreach ($requiredFields as $requiredField) {
            if (!array_key_exists($requiredField, $extras)) {
                throw new \InvalidArgumentException("Extras missing '{$requiredField}' property");
            }
        }
    }

    private function sendRespond(AbMessage $abMessage, array $extras)
    {
        if ($abMessage->isInternal()) {
            return;
        }

        $this->checkExtras($extras, ['emailClassType']);

        $messageType = $extras['emailClassType'];
        $abRequest = $abMessage->getRequest();
        $post = $abMessage->getPost();
        $messageReplacedVars = MessageFormatter::getMessageReplacedVars($abRequest);
        $post = str_replace(array_keys($messageReplacedVars), array_values($messageReplacedVars), $post);
        $messageCopy = clone $abMessage;
        $messageCopy->setPost(
            preg_replace(/** @lang RegExp */ "/<iframe(.+)<\/iframe>/ims", '', $post)
        );

        $template = new BookingRespond();
        $template->type = $messageType ? $messageType : BookingRespond::TYPE_BASIC;
        $template->request = $abRequest;
        $template->message = $messageCopy;
        $this->bookingRequestManager->sendEmail($template, !$abMessage->getFromBooker());
    }
}
