<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\MailboxOfferTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class ProblemRecognizingSubmission extends AbstractTemplate
{
    use MailboxOfferTrait;

    /**
     * @var string
     */
    public $fromEmail;

    /**
     * @var string
     */
    public $toEmail;

    /**
     * @var string
     */
    public $subject;

    public static function getDescription(): string
    {
        return "Error parsing email";
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('hasMailbox', CheckboxType::class, [
            'label' => /** @Ignore */ 'Has mailbox',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());

        $template->fromEmail = "test@gmail.com";
        $template->toEmail = "test@awardwallet.com";
        $template->subject = 'Hawaiian Airlines Itinerary';

        if (isset($options['hasMailbox'])) {
            $template->hasMailbox = $options['hasMailbox'];
        }

        return $template;
    }
}
