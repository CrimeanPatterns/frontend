<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class Timeout extends AbstractTemplate
{
    /** @var Account */
    public $account;

    public static function getDescription(): string
    {
        return 'Stop updating, timeout';
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
        $template->account = Tools::createAccount($template->getUser(), Tools::createProvider(), 1234567.6);
        $template->account->setLogin('login123456');

        return $template;
    }
}
