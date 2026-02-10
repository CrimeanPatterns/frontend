<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Support;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class VoteProgram extends AbstractTemplate
{
    public const TYPE_ADDED = 1;
    public const TYPE_FIXED = 2;

    public const TYPE_LIST = [
        self::TYPE_ADDED => "Added program",
        self::TYPE_FIXED => "Fixed program",
    ];

    /**
     * @var Provider
     */
    public $provider;

    /**
     * @var int
     */
    public $type = self::TYPE_FIXED;

    public static function getDescription(): string
    {
        return "Fixed/Added program";
    }

    public static function getStatus(): int
    {
        return self::STATUS_READY;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        /** @var Provider[] $providers */
        $providers = Tools::getProviders($container);
        $choices = [];

        foreach ($providers as $provider) {
            $choices[$provider->getProviderid()] = $provider->getDisplayname();
        }

        $builder->add('provider', ChoiceType::class, [
            'choices' => array_flip($choices),
        ]);
        $builder->add('type', ChoiceType::class, [
            'choices' => array_flip(self::TYPE_LIST),
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());

        $template->provider = $container->get('doctrine')->getRepository(Provider::class)->find(
            $options['provider'] ?? 7
        );

        if (isset($options['type'])) {
            $template->type = $options['type'];
        }

        return $template;
    }
}
