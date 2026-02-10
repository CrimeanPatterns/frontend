<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Form\Type\AwPlusGiftType;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\GiftingAwPlus;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GiftAWPlusCartPaidListener
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Mailer */
    private $mailer;

    /** @var SessionInterface */
    private $session;
    /**
     * @var CartUserSource
     */
    private $cartUserSource;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Mailer $mailer,
        SessionInterface $session,
        CartUserSource $cartUserSource
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->session = $session;
        $this->cartUserSource = $cartUserSource;
    }

    public function onCartMarkPaid(CartMarkPaidEvent $event): void
    {
        $cart = $event->getCart();

        if (!$cart->hasItemsByType([AwPlusGift::TYPE])) {
            return;
        }
        $user = $cart->getUser();
        $giftItem = $cart->getItemsByType([AwPlusGift::TYPE])->first();
        $giverUser = $this->entityManager->getRepository(Usr::class)->find($giftItem->getId());

        if (preg_match('/^([^\(]+)\((.*)\)$/is', $giftItem->getDescription(), $matches)) {
            $giftMessage = $matches[2];
        } else {
            $giftMessage = '';
        }

        $template = new GiftingAwPlus($user);
        $template->previewMode = false;
        $template->givingName = $giverUser->getFullName();
        $template->message = $giftMessage;
        $template->message = nl2br($template->message);
        $message = $this->mailer->getMessageByTemplate($template);

        $this->mailer->send($message);

        $this->session->remove(AwPlusGiftType::SESSION_GIFT_AWPLUS_DATA);
        $this->cartUserSource->clearUser();
    }
}
