<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class BusinessChoseToShare extends AbstractTemplate
{
    /**
     * @var Usr
     */
    public $inviter;

    public static function getDescription(): string
    {
        return "Business connection request";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);
        Tools::addMerchantForm($builder, $container);

        return $builder;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser());

        if (isset($options['Merchant'])) {
            $template->inviter = $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class)
                ->find($options['Merchant'])->getUserID();
        }

        if (!isset($template->inviter)) {
            $template->inviter = Tools::getDefaultMerchant($container)->getUserID();
        }

        return $template;
    }
}
