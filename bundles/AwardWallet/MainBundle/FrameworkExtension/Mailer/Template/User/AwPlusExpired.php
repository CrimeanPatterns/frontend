<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class AwPlusExpired extends AbstractTemplate
{
    public const VIA_TRIAL = 1;
    public const VIA_COUPON = 2;
    public const VIA_PAYMENT = 3;
    public const VIA_201 = 4;

    public float $lastPayment = 0;

    public ?int $lastType = null;

    public bool $trial = false;

    public static function getDescription(): string
    {
        return 'AwardWallet Plus Account expired';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('EmailKind', ChoiceType::class, [
            'label' => /** @Ignore */ 'Status received via',
            'choices' => [
                /** @Ignore */
                'Trial' => self::VIA_TRIAL,
                /** @Ignore */
                'Coupon' => self::VIA_COUPON,
                /** @Ignore */
                'Payment' => self::VIA_PAYMENT,
                /** @Ignore */
                '201 Subscription' => self::VIA_201,
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser(ACCOUNT_LEVEL_AWPLUS));
        $template->trial = false;

        if (!isset($options['EmailKind'])) {
            $options['EmailKind'] = self::VIA_PAYMENT;
        }

        switch ($options['EmailKind']) {
            case self::VIA_TRIAL:
                $template->trial = true;

                break;

            case self::VIA_COUPON:
                break;

            case self::VIA_PAYMENT:
                $template->lastPayment = 5.6;
                $template->lastType = AwPlusSubscription::TYPE;

                break;

            case self::VIA_201:
                $template->lastPayment = 9.99;
                $template->lastType = AT201Subscription6Months::TYPE;

                break;
        }

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
