<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\MailboxOfferTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class ReservationNew extends AbstractItineraryTemplate
{
    use MailboxOfferTrait;

    public const VARIANT_A = 'A';
    public const VARIANT_B = 'B';
    public const VARIANT_C = 'C';

    public ?string $variant = null;

    public static function getDescription(): string
    {
        return 'New travel reservation';
    }

    public static function getStatus(): int
    {
        return AbstractTemplate::STATUS_READY;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('hasMailbox', CheckboxType::class, [
            'label' => /** @Ignore */ 'Has mailbox',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);

        if (isset($options['hasMailbox'])) {
            $template->hasMailbox = $options['hasMailbox'];
        }

        if (isset($options['variant'])) {
            $template->variant = $options['variant'];
        }

        return $template;
    }
}
