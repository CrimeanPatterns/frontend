<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\ArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class Select2TypeAbstract extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (HiddenType::class === $this->getWidget() && !empty($options['configs']['multiple'])) {
            $builder->addViewTransformer(new ArrayToStringTransformer());
        } elseif (HiddenType::class === $this->getWidget() && empty($options['configs']['multiple']) && null !== $options['transformer']) {
            $builder->addModelTransformer($options['transformer']);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['configs'] = $options['configs'];
        $view->vars['hidden'] = $this->getWidget() == HiddenType::class;

        if (is_callable($options['init-data'])) {
            $view->vars['attr'] = array_merge($view->vars['attr'], ['data-init-data' => json_encode(call_user_func($options['init-data'], $view->vars['value']))]);
        } elseif (is_array($options['init-data'])) {
            $view->vars['attr'] = array_merge($view->vars['attr'], ['data-init-data' => json_encode($options['init-data'])]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            /** @Ignore */
            'placeholder' => 'Select a value',
            'allowClear' => false,
            'minimumInputLength' => 0,
            'width' => 'element',
        ];

        $resolver
            ->setDefaults([
                'error_bubbling' => false,
                'configs' => $defaults,
                'transformer' => null,
                'init-data' => null,
            ])
            ->setNormalizer(
                'configs',
                function (Options $options, $configs) use ($defaults) {
                    if (!is_array($configs)) {
                        return $configs;
                    }

                    return array_merge($defaults, $configs);
                }
            )
            ->setAllowedTypes('init-data', ['callable', 'array', 'null']);
    }

    public function getParent()
    {
        return $this->getWidget();
    }

    public function getBlockPrefix()
    {
        return 'select2';
    }

    abstract protected function getWidget();
}
