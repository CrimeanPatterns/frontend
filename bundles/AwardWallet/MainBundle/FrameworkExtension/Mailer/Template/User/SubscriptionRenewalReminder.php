<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubscriptionRenewalReminder extends AbstractTemplate
{
    public ?int $subscriptionType = null;

    public ?\DateTime $expirationDate = null;

    public ?string $amount = null;

    public ?string $paymentMethod = null;

    public static function getDescription(): string
    {
        return 'AwardWallet subscription renewal reminder';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var TranslatorInterface $translation */
        $translation = $container->get('translator');
        /** @var LocalizeService $localizer */
        $localizer = $container->get(LocalizeService::class);
        $template = new static(Tools::createUser());
        $template->subscriptionType = Usr::SUBSCRIPTION_TYPE_AWPLUS;
        $template->expirationDate = new \DateTime('+3 day');
        $template->amount = $translation->trans('price_per_period', [
            '%price%' => $localizer->formatCurrency(
                SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR),
                'USD',
                true,
                $template->locale
            ),
            '%period%' => $translation->trans(
                'years',
                ['%count%' => 1],
                'messages',
                $template->lang
            ),
        ], 'messages', $template->lang);
        $template->paymentMethod = 'Credit Card';

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
