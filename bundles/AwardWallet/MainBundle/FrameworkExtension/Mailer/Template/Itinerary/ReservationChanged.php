<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\MailboxOfferTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class ReservationChanged extends AbstractItineraryTemplate
{
    use MailboxOfferTrait;

    public static function getDescription(): string
    {
        return 'Travel reservation was updated';
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
        $template = parent::createFake($container, array_merge($options, ['changed' => true]));

        if (isset($options['hasMailbox'])) {
            $template->hasMailbox = $options['hasMailbox'];
        }

        return $template;
    }
}
