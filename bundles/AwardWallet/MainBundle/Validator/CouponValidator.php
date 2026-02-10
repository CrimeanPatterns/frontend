<?php

namespace AwardWallet\MainBundle\Validator;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus20Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard as OneCardItem;
use AwardWallet\MainBundle\Entity\CartItem\PlusItems;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\CouponItem;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\CouponModel;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CouponValidator implements TranslationContainerInterface
{
    public const ERROR_FIRST_TIME_ONLY_AW = 1;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var ExpirationCalculator
     */
    private $expirationCalculator;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var DateTimeIntervalFormatter
     */
    private $intervalFormatter;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        LocalizeService $localizer,
        ExpirationCalculator $expirationCalculator,
        AuthorizationCheckerInterface $authorizationChecker,
        DateTimeIntervalFormatter $intervalFormatter
    ) {
        $this->translator = $translator;
        $this->em = $em;
        $this->localizer = $localizer;
        $this->expirationCalculator = $expirationCalculator;
        $this->authorizationChecker = $authorizationChecker;
        $this->intervalFormatter = $intervalFormatter;
    }

    public function validateCoupon(CouponModel $model, ExecutionContextInterface $context, $errorPath = null)
    {
        /** @var Coupon $coupon */
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy(['code' => $model->getCoupon()]);

        if ($coupon === null) {
            return $this->translator->trans('coupon.invalid', [], 'validators');
        }

        /** @var Cart $cart */
        $cart = $model->getEntity();
        $user = $cart->getUser();

        if ($coupon->isExpired()) {
            return $this->translator->trans('coupon.expired', [], 'validators');
        }

        if ($coupon->getMaxuses() > 0 && $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->getNumberOfUses($coupon) >= $coupon->getMaxuses()) {
            return $this->translator->trans('coupon.number-uses', [], 'validators');
        }

        $discount = $cart->getDiscountAmount($coupon);

        if ($coupon->getFirsttimeonly()) {
            if ($coupon->getUser() && $user->getId() == $coupon->getUser()->getId()) {
                return $this->translator->trans('coupon.own-coupon', [], 'validators');
            }

            $allowFirstTimeOnly = $this->allowApplyFirstTimeOnlyCoupon($user);

            if (!$allowFirstTimeOnly) {
                foreach ($coupon->getItems() as $couponItem) {
                    /** @var CouponItem $couponItem */
                    if ($couponItem->getCartItemType() == OneCardItem::TYPE) {
                        $serviceName = 'service.one-card';
                        $typeIds = [OneCardItem::TYPE];
                    } else {
                        $serviceName = 'service.aw-plus';
                        $typeIds = PlusItems::getTypes();
                    }

                    if ($user->paidFor($typeIds)) {
                        if ($couponItem->getCartItemType() == OneCardItem::TYPE) {
                            $serviceName = $this->translator->trans(/** @Ignore */
                                $serviceName
                            );

                            return $this->translator->trans(
                                'coupon.already-used-first-time',
                                ['%serviceName%' => $serviceName],
                                'validators'
                            );
                        } else {
                            $builder = $context->buildViolation($this->translator->trans(
                                'coupon.never-had-awplus',
                                [],
                                'validators'
                            ))
                                ->setCause($this->getErrorContext(self::ERROR_FIRST_TIME_ONLY_AW, $user));

                            if (isset($errorPath)) {
                                $builder->atPath($errorPath);
                            }
                            $builder->addViolation();

                            return null;
                        }
                    }
                }
            }

            if (
                $discount > 0 && $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS
                && $cart->hasItemsByType([AwPlus::TYPE, AwPlus1Year::TYPE, AwPlus20Year::TYPE])
                && !$allowFirstTimeOnly
            ) {
                $builder = $context->buildViolation($this->translator->trans(
                    'coupon.never-had-awplus',
                    [],
                    'validators'
                ))
                    ->setCause($this->getErrorContext(self::ERROR_FIRST_TIME_ONLY_AW, $user));

                if (isset($errorPath)) {
                    $builder->atPath($errorPath);
                }
                $builder->addViolation();

                return null;
            }
        }

        if ($user->usedCoupon($coupon)) {
            return $this->translator->trans('coupon.already-used', [], 'validators');
        }

        if ($cart->hasItemsByType([Discount::TYPE])) {
            return $this->translator->trans('coupon.not-apply-same-time', [], 'validators');
        }

        if ($coupon->hasCartItemTypes([AwPlusSubscription::TYPE]) && $user->getSubscription() !== null) {
            return $this->translator->trans( /** @Desc("You already have a current subscription to AwardWallet Plus, unfortunately this coupon will not work with your account.") */ 'coupon.not-for-subscribers', [], 'validators');
        }

        if ($coupon->getDiscount() < 100 && $this->authorizationChecker->isGranted('SITE_MOBILE_AREA')) {
            return $this->translator->trans( /** @Desc("This coupon could be used only via desktop version of the site") */ "coupon.desktop_only");
        }
    }

    public function allowApplyFirstTimeOnlyCoupon(Usr $user)
    {
        $q = $this->em->createQuery("select ci from AwardWallet\MainBundle\Entity\CartItem ci
            join ci.cart c
            where c.paydate is not null and c.user = :userId");
        $results = $q->execute(["userId" => $user]);
        $paid = false;

        foreach ($results as $cartItem) {
            /** @var CartItem $cartItem */
            if ($cartItem instanceof AwPlus && !in_array($cartItem::TYPE, CartItem::TRIAL_TYPES, true)) {
                $paid = true;

                break;
            }
        }

        return !$paid;
    }

    public function getErrorContext($code, Usr $user)
    {
        $result = [];
        $cartRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);

        switch ($code) {
            case self::ERROR_FIRST_TIME_ONLY_AW:
                if ($user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) {
                    $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());
                    $result['status'] = $this->translator->trans('account_type.awplus');

                    if (isset($expiration['date'])) {
                        $expiration['date'] = new \DateTime("@" . $expiration['date']);
                        $result['expires'] = $this->translator->trans('account_type.awplus.expires', [
                            '%expiration_date%' => $this->localizer->formatDate($expiration['date'], 'short', $user->getLocale()),
                            '%expiration_verbal%' => $this->intervalFormatter->formatDuration(
                                new \DateTime(),
                                $expiration['date'],
                                true,
                                false,
                                true
                            ),
                        ]);
                    }
                } else {
                    $result['status'] = $this->translator->trans('user.account_type.regular');
                }
                /** @var Cart $lastCart */
                $lastCart = $cartRep->getLastAwPlusCart($user);

                if (!isset($lastCart)) {
                    return [];
                }

                if ($lastCart->hasItemsByType(CartItem::TRIAL_TYPES)) {
                    $result['upgradeVia'] = $this->translator->trans('upgraded-via-trial');
                } elseif (!empty($lastCart->getCouponcode())) {
                    $result['upgradeVia'] = $this->translator->trans('upgraded-via-coupon');
                } else {
                    $result['upgradeVia'] = $this->translator->trans('upgraded-via-pay');
                }
                $result['upgradeOn'] = $this->localizer->formatDate($lastCart->getPaydate(), 'short', $user->getLocale());

                break;
        }

        return $result;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('coupon.invalid', 'validators'))->setDesc('Invalid coupon code'),
            (new Message('coupon.expired', 'validators'))->setDesc('Expired coupon code'),
            (new Message('coupon.number-uses', 'validators'))->setDesc('The number of times this coupon could be used has been exceeded by other AwardWallet members.'),
            (new Message('coupon.not-match-selected-items', 'validators'))->setDesc('Coupon does not match selected items'),
            (new Message('coupon.already-used-first-time', 'validators'))->setDesc('This coupon code is only valid for the first time users of %serviceName% service. Our records indicate that you have already used %serviceName% in the past; therefore, this coupon can\'t be used with your account.'),
            (new Message('coupon.already-used', 'validators'))->setDesc('This coupon code can be used only once. Our records indicate that you have already used this coupon.'),
            (new Message('coupon.not-apply-same-time', 'validators'))->setDesc('You can not apply coupons and donate at the same time'),
            (new Message('coupon.never-had-awplus', 'validators'))->setDesc('This coupon code is only valid for the users who never had AwardWallet Plus in the past.'),
            (new Message('coupon.own-coupon', 'validators'))->setDesc('You can not use your own coupon.'),
            (new Message('service.one-card'))->setDesc('AwardWallet OneCard'),
            (new Message('service.aw-plus'))->setDesc('AwardWallet Plus'),
            (new Message('upgraded-via-trial'))->setDesc('Automatic promotion during registration'),
            (new Message('upgraded-via-coupon'))->setDesc('Coupon code'),
            (new Message('upgraded-via-pay'))->setDesc('Paid membership'),
            (new Message('coupon.about-account'))->setDesc('Here is what we see about your account'),
            (new Message('coupon.current-account-status'))->setDesc('Current account status'),
            (new Message('coupon.last-upgrade-via'))->setDesc('Last upgrade received via'),
            (new Message('coupon.last-upgrade-on'))->setDesc('Last upgrade received on'),
            (new Message('coupon.used-first-time-notice'))->setDesc('Therefore, this coupon will not work for you. There is nothing wrong with this code it is just designed to work only for users who never had AwardWallet Plus in the past (typically you would apply it during account registration). Please note that we don’t give out coupon codes via our support channels so %bold_on%please don’t write to us asking to be upgraded%bold_off%. To upgrade / extend your membership you need to either find another coupon (without asking us for it) or pay for the upgrade. %bold_on%AwardWallet support does not give away coupons%bold_off%.'),
        ];
    }
}
