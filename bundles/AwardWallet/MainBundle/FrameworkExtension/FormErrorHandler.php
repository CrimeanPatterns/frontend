<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormErrorHandler
{
    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param bool $children
     * @param bool $addLabel
     * @return array errorText, label, name (input name)
     */
    public function getFormErrors(Form $form, $children = false, $addLabel = true)
    {
        $errors = [];

        // Form is not submitted
        if (!$form->isSubmitted()) {
            $errors[] = [
                'errorText' => 'Form is not submitted',
                /** @Ignore */
                'label' => null,
                'name' => $this->getFullName($form),
            ];
        }

        /** @var Form $v */
        foreach ($form->all() as $v) {
            if ($children && count($v) > 0) {
                $errors = array_merge($errors, $this->getFormErrors($v, $children, $addLabel));

                continue;
            }
            $e = $v->getErrors();

            if (!sizeof($e)) {
                continue;
            }

            /** @var FormError $singleError */
            foreach ($e as $singleError) {
                $labelAttr = $v->getConfig()->getOption('label_attr');
                $error = $this->getUserFriendlyErrorMessage(
                    $singleError->getMessage(),
                    $singleError->getMessageParameters(),
                    $singleError->getMessagePluralization(),
                    (!isset($labelAttr['data-error-label']) || $labelAttr['data-error-label'] === true) && $addLabel ?
                        $v->getConfig()->getOption('label') : null
                );
                $errors[] = [
                    'errorText' => $error,
                    /** @Ignore */
                    'label' => $v->getConfig()->getOption('label'),
                    'name' => $this->getFullName($v),
                ];
            }
        }

        if (!sizeof($errors)) {
            $e = $form->getErrors();

            foreach ($e as $singleError) {
                /** @var FormError singleError */
                $patternError = $singleError->getMessageTemplate();
                $parametersError = $singleError->getMessageParameters();
                /** @Ignore */
                $patternError = $this->translator->trans($patternError, $parametersError, 'validators');
                $errors[] = [
                    'errorText' => $patternError,
                    /** @Ignore */
                    'label' => null,
                    'name' => (!empty($singleError->getCause()) && preg_match('#\[(\w+)\]$#ims', $singleError->getCause()->getPropertyPath(), $matches) ? $form->getName() . '[' . $matches[1] . ']' : $this->getFullName($form)),
                ];
            }
        }

        return $errors;
    }

    public function getUserFriendlyErrorMessage($patternError, $parametersError, $pluralizationError, $label = null)
    {
        if (isset($label)) {
            $parametersError['{{ name }}'] = $label;

            if (strpos($patternError, 'This value') !== false) {
                $patternError = str_replace('This value', '{{ name }}', $patternError);
            } else {
                $patternError = '{{ name }}: ' . $patternError;
            }
        }

        if (is_numeric($pluralizationError) && strpos($patternError, '|') !== false) {
            /** @Ignore */
            return $this->translator->trans($patternError, array_merge($parametersError, ['%count%' => $pluralizationError]), 'validators');
        } else {
            /** @Ignore */
            return $this->translator->trans($patternError, $parametersError, 'validators');
        }
    }

    private function getFullName(Form $form)
    {
        $name = '';
        $parent = $form;

        while (!empty($parent)) {
            $isMainForm = $parent->getParent() === null;
            $name = (($isMainForm) ? $parent->getName() : '[' . $parent->getName() . ']') . $name;
            $parent = $parent->getParent();
        }

        return $name;
    }
}
