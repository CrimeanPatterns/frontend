<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class LufthansaExpiration extends AbstractTemplate
{
    /** @var array */
    public $account;

    public static function getDescription(): string
    {
        return 'Lufthansa expiration warning';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->account = [];
        $template->date = new \DateTime();
        $template->login = 1234;

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
