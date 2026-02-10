<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\SpentAnalysis;

use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardSwitcherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            public function transform($value)
            {
                return (bool) $value;
            }

            public function reverseTransform($value)
            {
                return (bool) $value;
            }
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $definition = $form->getParent()->getParent()->getParent()->getData()['definition']['cards'][$form->getName()];
        $view->vars['label'] = $definition['creditCardName'];

        if (StringUtils::isNotEmpty($definition['description'] ?? null)) {
            $view->vars['attr']['notice'] = $definition['description'];
        }

        if (isset($definition['existedCard']) && $definition['existedCard']) {
            $view->vars['attr']['icons'][] = 'card-have';
        }

        if (isset($definition['isBusiness'])) {
            $view->vars['attr']['icons'][] = $definition['isBusiness'] ? 'card-business' : 'card-personal';
        }

        if (!empty($definition['creditCardImage'])) {
            $view->vars['attr']['creditCardImage'] = $definition['creditCardImage'];
        }

        $view->vars['checked'] = \is_scalar($view->vars['value']) ?
            (bool) $view->vars['value'] :
            false;
    }

    public function getParent()
    {
        return FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['compound' => false]);
    }

    public function getBlockPrefix()
    {
        return 'switcher';
    }
}
