<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class TwoFactorAuth extends AbstractTemplate
{
    public $disabled = true;

    public static function getDescription(): string
    {
        return "Two Factor auth enabled/disabled";
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('disabled', CheckboxType::class, [
            'label' => /** @Ignore */ 'Disable auth?',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->disabled = isset($options['disabled']) && $options['disabled'];

        return $template;
    }
}
