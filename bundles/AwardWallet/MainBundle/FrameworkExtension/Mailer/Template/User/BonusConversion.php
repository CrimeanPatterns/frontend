<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\BonusConversion as BonusConversionEntity;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class BonusConversion extends AbstractTemplate
{
    /**
     * @var BonusConversionEntity
     */
    public $conversion;

    public static function getDescription(): string
    {
        return "Bonus Conversion";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser());

        $provider = Tools::createProvider();
        $cur = new Currency();
        $cur->setName("Miles");
        $provider->setCurrency($cur);
        $account = Tools::createAccount($user, $provider, 100500);

        $numberProviderProp = Tools::createProviderProperty($provider, "Number", PROPERTY_KIND_NUMBER);
        $numberProp = Tools::createAccountProperty($numberProviderProp, $account, "2233445566");
        $account->setProperties(new ArrayCollection([$numberProp]));

        $template->conversion = Tools::createBonusConversion($user, "American", 1976, $account);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
