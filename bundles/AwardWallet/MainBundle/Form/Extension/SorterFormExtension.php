<?php

namespace AwardWallet\MainBundle\Form\Extension;

use AwardWallet\MainBundle\Form\Helper\FormOrderingHelper;
use AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Sorter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SorterFormExtension extends AbstractTypeExtension
{
    public const ATTRIBUTE_NAME = 'form_sorter';

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            self::ATTRIBUTE_NAME => null,
        ]);
        $resolver->setAllowedTypes(self::ATTRIBUTE_NAME, [Sorter::class, 'null']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $formConfig = $form->getConfig();
        /** @var Sorter $sorter */
        $sorter = $formConfig->getOption(self::ATTRIBUTE_NAME) ?? $formConfig->getAttribute(self::ATTRIBUTE_NAME);

        if ($sorter) {
            $view->children = FormOrderingHelper::useFormViewSorter($view->children, $sorter);
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
