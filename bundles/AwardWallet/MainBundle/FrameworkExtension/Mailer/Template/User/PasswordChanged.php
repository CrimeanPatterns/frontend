<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class PasswordChanged extends AbstractTemplate
{
    public static function getDescription(): string
    {
        return "AwardWallet Password Changed";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        return new static(Tools::createUser());
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
