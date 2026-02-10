<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent;

use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class Invitation extends AbstractTemplate
{
    /**
     * @var Invites
     */
    public $invite;

    /**
     * @var bool
     */
    public $reminder = false;

    public static function getDescription(): string
    {
        return 'Invitation to AwardWallet.com (by email address)';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('reminder', CheckboxType::class, [
            'required' => false,
            /** @Ignore */
            'label' => 'Re-invitation',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($email = "test@test.com");

        $template->invite = Tools::createInvites(
            Tools::createUser(),
            $email,
            "abcdef"
        );
        $template->reminder = isset($options['reminder']) && $options['reminder'];

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
