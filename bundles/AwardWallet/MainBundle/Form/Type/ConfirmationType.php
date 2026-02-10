<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Account\Template;
use AwardWallet\MainBundle\Form\Transformer\DateTransformer;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ConfirmationType.
 *
 * @property ObjectManager $em
 */
class ConfirmationType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'confirmation_type';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Template $template */
        $template = $options['confirmation_template'];

        foreach ($template->fields as $single) {
            if ('Pass' === $single['id']) {
                $single['type'] = PasswordType::class;
            }

            if (CheckboxType::class === $single['type']) {
                unset($single['options']['attr']['class']);
            }

            if (ChoiceType::class === $single['type']) {
                $single['type'] = Select2ChoiceType::class;
            }

            if (DatePickerType::class === $single['type']) {
                $single['options']['input'] = 'datetime';
                $single['options']['validation_groups'] = false;
            }

            if (array_key_exists('constraint', $single)) {
                $single['options']['constraints'] = $single['constraint'];
            }

            if (in_array($single['type'], [TextType::class, PasswordType::class])) {
                $single['options']['allow_urls'] = true;
            }

            $builder->add($single['id'], $single['type'], $single['options']);

            if (DatePickerType::class === $single['type']) {
                $builder->get($single['id'])->addModelTransformer(new DateTransformer());
            }
        }

        $builder->add("browserExtensionAllowed", HiddenType::class);
        $builder->add("channel", HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['confirmation_template']);
        $resolver->setAllowedTypes('confirmation_template', Template::class);
    }
}
