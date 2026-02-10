<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Citi;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class CardOfferActivated extends AbstractTemplate
{
    public ?string $offerSubject;

    public static function getDescription(): string
    {
        return 'Automatically detected an offer email';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->offerSubject = 'User, use your Citi Card and earn bonus ThankYou® Points on eligible everyday purchases';

        return $template;
    }
}
