<?php

namespace AwardWallet\MainBundle\Form\Extension\JsonFormExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonFormExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isJsonForm($options)) {
            return;
        }

        $builder->setRequestHandler(new JsonRequestHandler());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'json_form' => false,
            'jsProviderExtension' => null,
            'jsFormInterface' => null,
            'submitData' => false,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['jsFormInterface'] = $form->getConfig()->getAttribute('jsFormInterface');
        $view->vars['jsProviderExtension'] = $form->getConfig()->getAttribute('jsProviderExtension');
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    private function isJsonForm(array $options)
    {
        return isset($options['json_form']) && $options['json_form'];
    }
}
