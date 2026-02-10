<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\TwoChoicesTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TwoChoicesOrTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new TwoChoicesTransformer($options['yes_value'], $options['no_value']));
        $builder->add('choice', ChoiceType::class, [
            'choices' => [
                /** @Ignore */
                $options['yes_label'] => $options['yes_value'],
                /** @Ignore */
                $options['no_label'] => $options['no_value'],
            ],
            'required' => true,
            'multiple' => false,
            'expanded' => true,
        ]);
        $builder->add('text', $options['text_widget'], $options['text_widget_options']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['widget_options'] = $options['widget_options'];
        $view->vars['before_text'] = $options['before_text'];
        $view->vars['title_text'] = $options['title_text'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'yes_value' => 1,
            'no_value' => 0,
            'yes_label' => 'yes',
            'no_label' => 'no',
            'text_widget' => TextType::class,
            'text_widget_options' => [],
            'before_text' => null,
            'title_text' => null,
            'widget_options' => [
                'yes_without_help' => true,
                'default_text' => '',
            ],
            'error_bubbling' => false,
            'compound' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'two_choices_or_text';
    }
}
