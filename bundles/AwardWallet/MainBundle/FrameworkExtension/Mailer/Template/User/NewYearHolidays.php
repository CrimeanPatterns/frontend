<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class NewYearHolidays extends AbstractTemplate
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var \DateTime
     */
    public $endDate;

    public static function getDescription(): string
    {
        return 'Automated email reply on NY holidays';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('auth', CheckboxType::class, [
            'required' => false,
            'label' => /** @Ignore */ 'To aw user',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static();

        if (isset($options['auth']) && $options['auth']) {
            $template->toUser(Tools::createUser());
        }
        $template->name = StringHandler::getRandomName()['FirstName'];
        $template->endDate = new \DateTime((date('Y') + 1) . '-01-11');

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
