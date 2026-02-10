<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CartVoter extends AbstractVoter
{
    /** @var CartUserSource */
    private $cartUserSource;

    public function __construct(ContainerInterface $container, CartUserSource $cartUserSource)
    {
        parent::__construct($container);

        $this->cartUserSource = $cartUserSource;
    }

    public function view(TokenInterface $token, Cart $cart)
    {
        $context = $this->container->get('security.authorization_checker');
        /** @var Usr $user */
        $user = $token->getUser();
        $cartUser = $cart->getUser();

        if (!$cartUser instanceof Usr) {
            return false;
        }

        // проверяем есть ли доступ к просмотру корзины. незалогиненный пользователь, но оплативший не более получаса назад
        if (!$user instanceof Usr) {
            $currentCartOwner = $this->cartUserSource->getCartOwner();

            if ($currentCartOwner === null || $currentCartOwner->getId() !== $cartUser->getId()) {
                return false;
            }

            if ($cart->getPaydate() === null) {
                return false;
            }

            if ($cart->getPaydate()->getTimestamp() + (0.5 * DateTimeUtils::SECONDS_PER_HOUR) > (new \DateTime())->getTimestamp()) {
                return true;
            }

            return false;
        }

        if (
            (
                $context->isGranted('SITE_BUSINESS_AREA')
                && ($businessUser = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user))
                && ($businessUser->getUserid() == $cartUser->getUserid())
            )
            || ($user->getUserid() == $cartUser->getUserid())
        ) {
            return true;
        }

        // Пользователь оплатил подарочную(не принадлежающую ему) корзину
        if ($cart->hasItemsByType([AwPlusGift::TYPE])) {
            $giftItem = $cart->getItemsByType([AwPlusGift::TYPE])->first();

            if ($giftItem->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    protected function getAttributes()
    {
        return [
            'VIEW' => [$this, 'view'],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Cart';
    }
}
