<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\View\Block\GroupTitle;
use AwardWallet\MobileBundle\Form\View\Block\Table;
use AwardWallet\MobileBundle\Form\View\Block\WarningLink;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileCouponResultType extends AbstractType implements TranslationContainerInterface
{
    private $schemeAndHost;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $tr;

    /**
     * @var LocalizeService
     */
    private $localizer;

    /**
     * @var Usr
     */
    private $user;

    public function __construct(
        $schemeAndHost,
        RouterInterface $router,
        TranslatorInterface $tr,
        LocalizeService $localizer,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->schemeAndHost = $schemeAndHost;
        $this->router = $router;
        $this->tr = $tr;
        $this->localizer = $localizer;
        $this->user = $tokenStorage->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Cart $cart */
        $cart = $options['cart'];
        $builder->setAttribute('submit_label', 'user.coupon.button.continue');

        if ($cart->getTotalPrice() > 0) {
            $builder->add("warn", BlockContainerType::class, [
                'blockData' => new WarningLink(
                    $this->schemeAndHost . $this->router->generate('aw_cart_common_paymenttype'),
                    $this->tr->trans('payment.dont_support', [], 'mobile')
                ),
                'attr' => [
                    'page' => 'Payment',
                ],
            ]);
        } elseif ($cart->hasItemsByType([OneCard::TYPE])) {
            $builder->add("warn", BlockContainerType::class, [
                'blockData' => new WarningLink(
                    $this->schemeAndHost . $this->router->generate('aw_one_card'),
                    $this->tr->trans('onecard.dont_support', [], 'mobile')
                ),
                'attr' => [
                    'page' => 'Onecard',
                ],
            ]);
        }

        $builder->add("head", BlockContainerType::class, [
            'blockData' => new GroupTitle(
                $cart->hasItemsByType([AwPlus::TYPE, AwPlus1Year::TYPE]) ?
                    $this->tr->trans('user.coupon.title.free-upgrade') :
                    $this->tr->trans('user.coupon.title.coupon-used')
            ),
            'attr' => [
                'class' => 'small',
            ],
        ]);

        $rows = [];

        foreach ($cart->getItems() as $item) {
            if (!$item instanceof Discount) {
                if ($item instanceof OneCard) {
                    $rows[] = [
                        $item->getName(),
                        '<strong>' . $this->localizer->formatNumber($item->getQuantity()) . ' x ' . $this->localizer->formatCurrency($item->getPrice(), 'USD', false) . '</strong>',
                    ];
                } else {
                    $rows[] = [
                        $item->getName(),
                        '<strong>' . $this->localizer->formatCurrency($item->getPrice(), 'USD', false) . '</strong>',
                    ];
                }
            }
        }

        foreach ($cart->getItems() as $item) {
            if ($item instanceof Discount) {
                $rows[] = [
                    $item->getName(),
                    '<strong>' . $this->localizer->formatCurrency($item->getPrice(), 'USD', false) . '</strong>',
                ];
            }
        }
        $rows[] = [
            '<strong class="red">' . $this->tr->trans('user.coupon.table.total') . ':</strong>',
            '<span class="red big">' . $this->localizer->formatCurrency($cart->getTotalPrice(), 'USD', false) . '</span>',
        ];
        $builder->add("table", BlockContainerType::class, ['blockData' => new Table($rows)]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(["cart"]);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function getBlockPrefix()
    {
        return 'mobile_profile_result_coupon';
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('onecard.dont_support', 'mobile'))->setDesc('At the moment we don\'t have a mobile-friendly version of the interface to order AwardWallet OneCards. If you wish you can access the desktop version of that page to order the card.'),
            (new Message('payment.dont_support', 'mobile'))->setDesc('At the moment we don\'t have a mobile-friendly version of the payment interface. If you wish you can access the desktop version of that page.'),
        ];
    }
}
