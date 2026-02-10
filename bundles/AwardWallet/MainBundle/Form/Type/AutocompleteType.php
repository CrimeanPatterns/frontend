<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutocompleteType extends AbstractType
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $empty = '';
        $resolver->setDefaults([
            'route' => '',
            'placeholder' => $empty,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->vars['data']) {
            $data = $form->getData();

            if (is_object($data) && method_exists($data, 'getFullName')) {
                $view->vars['text_value'] = $form->getData()->getFullName();
            } else {
                $view->vars['text_value'] = $options['placeholder'];
            }
        } else {
            $view->vars['text_value'] = $options['placeholder'];
        }

        if ($options['route']) {
            $view->vars['attr']['data-url'] = $this->router->generate($options['route']);
        }
        $view->vars['attr']['data-empty'] = $options['placeholder'];
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'autocomplete';
    }
}
