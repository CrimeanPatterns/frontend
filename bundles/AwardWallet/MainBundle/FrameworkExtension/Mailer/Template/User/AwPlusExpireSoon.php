<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class AwPlusExpireSoon extends AbstractTemplate
{
    public ?\DateTime $expireDate = null;

    public ?int $lastType = null;

    public static function getDescription(): string
    {
        return 'AwardWallet Plus membership expires soon';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser(ACCOUNT_LEVEL_AWPLUS));
        $template->expireDate = new \DateTime("+1 week");
        $template->lastType = AwPlusSubscription::TYPE;

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
