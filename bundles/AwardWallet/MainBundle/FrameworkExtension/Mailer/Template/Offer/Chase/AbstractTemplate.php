<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate as BaseAbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

abstract class AbstractTemplate extends BaseAbstractTemplate
{
    public $lang = 'en';

    public $locale = 'en_US';

    /**
     * @var string
     */
    public $location;

    public static function createFake(ContainerInterface $container, $options = [])
    {
        $template = new static(Tools::createUser());

        if (isset($options['location'])) {
            $template->location = $options['location'];
        } else {
            $template->location = 'Denver';
        }

        return $template;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('location', TextType::class, [
            'label' => /** @Ignore */ 'Location',
            'data' => 'Denver',
            'required' => true,
        ]);

        return $builder;
    }

    public static function getStatus(): int
    {
        return BaseAbstractTemplate::STATUS_READY;
    }
}
