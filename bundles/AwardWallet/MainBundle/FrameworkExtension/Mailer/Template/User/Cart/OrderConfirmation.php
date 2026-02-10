<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\Cart;

use AwardWallet\MainBundle\Entity\AbBookerInfo;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class OrderConfirmation extends AbstractTemplate
{
    /**
     * @var AbBookerInfo
     */
    public $merchant;

    /**
     * @var Cart
     */
    public $cart;

    /**
     * @var array
     */
    public $paymentTypes = [];

    /**
     * @var string only for staff
     */
    public $developerInfo;

    public function __construct($to = null, $toBusiness = false)
    {
        global $arPaymentTypeName;
        parent::__construct($to, $toBusiness);

        $this->paymentTypes = $arPaymentTypeName;
    }

    public static function getDescription(): string
    {
        return "Order Confirmation";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        global $arPaymentTypeName;
        $builder = parent::tuneManagerForm($builder, $container);
        Tools::addMerchantForm($builder, $container);
        $builder->add('paymentType', ChoiceType::class, [
            'label' => /** @Ignore */ 'Payment Type',
            'choices' => array_flip($arPaymentTypeName),
        ]);

        return $builder;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser());

        if (isset($options['Merchant'])) {
            $template->merchant = $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class)
                ->find($options['Merchant']);
        }

        $cart = Tools::createCart(
            $user,
            $options['paymentType'] ?? Cart::PAYMENTTYPE_CREDITCARD,
            $user->getFirstname(),
            $user->getLastname(),
            $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Country::class)->findOneByCode("US"),
            "Abc Street, 123",
            "New York",
            234234
        );
        $cart->setCreditcardtype('VISA');
        $cart->setCreditcardnumber('XXXXXXXXXXXX1234');

        $item = new AwPlusSubscription();
        $startDate = new \DateTime();

        $item->setName('AwardWallet Plus yearly subscription');
        $item->setDescription("12 months (starting from {$startDate->format('m/d/Y')})");
        $cart->addItem($item);

        $item = new Discount();
        $item->setPrice(AwPlus1Year::EARLY_SUPPORTER_DISCOUNT * -1);
        $item->setName('Early supporter discount (thank you)');
        $cart->addItem($item);

        $onecard = new OneCard();
        $onecard->setName('OneCard Credits');
        $cart->addItem($onecard);
        $template->cart = $cart;

        $template->developerInfo = sprintf("User: %s (%d)", $user->getFullName(), $user->getUserid());

        return $template;
    }
}
