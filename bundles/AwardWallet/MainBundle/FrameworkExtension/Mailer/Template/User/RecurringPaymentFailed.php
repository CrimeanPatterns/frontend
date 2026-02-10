<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class RecurringPaymentFailed extends AbstractTemplate
{
    public const PAYMENT_SOURCE_PAYPAL = 1;
    public const PAYMENT_SOURCE_CC = 2;

    public bool $semiAnnualSubscription = false;

    public int $paymentSource = self::PAYMENT_SOURCE_CC;

    public ?string $ccNumber = null;

    public ?float $amount = null;

    public ?\DateTimeInterface $throughDate = null;

    public ?string $paymentLink;

    public int $subscriptionType = Usr::SUBSCRIPTION_TYPE_AWPLUS;
    public string $subscriptionPeriod = SubscriptionPeriod::DURATION_1_YEAR;

    public static function getDescription(): string
    {
        return 'AwardWallet Plus Subscription Failed to Renew';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('semiAnnual', CheckboxType::class, [
            'label' => /** @Ignore */ 'Semi-annual Subscription',
        ]);
        $builder->add('source', ChoiceType::class, [
            'label' => /** @Ignore */ 'Payment Type',
            'choices' => [
                /** @Ignore */
                'Credit Card' => self::PAYMENT_SOURCE_CC,
                /** @Ignore */
                'PayPal' => self::PAYMENT_SOURCE_PAYPAL,
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());

        $template->semiAnnualSubscription = isset($options['semiAnnual']) && $options['semiAnnual'];
        $template->paymentSource = $options['source'] ?? self::PAYMENT_SOURCE_CC;
        $template->ccNumber = '1234';

        $template->amount = AwPlusSubscription::PRICE;

        $template->throughDate = $template->semiAnnualSubscription ? new \DateTime("+6 month") : new \DateTime("+1 year");
        $template->paymentLink = "/some/upgrade/link";

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
