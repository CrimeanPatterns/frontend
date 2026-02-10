<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilder;

class SapphirePreferredA extends AbstractTemplate
{
    public $link = 'https://awardwallet.com/blog/link/travel-plans-sapphire-preferred-a/';

    /**
     * @var \DateTime
     */
    public $date;

    public static function getEmailKind(): string
    {
        return 'travel_plans_sapphire_preferred_a';
    }

    public static function getDescription(): string
    {
        return 'Sapphire Preferred (A)';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('date', DateType::class, [
            'label' => /** @Ignore */ 'Date',
            'data' => new \DateTime(),
            'required' => true,
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        if (isset($options['date'])) {
            $template->date = $options['date'];
        } else {
            $template->date = new \DateTime();
        }

        return $template;
    }
}
