<?php

namespace AwardWallet\MainBundle\Form\Account;

use AwardWallet\MainBundle\Entity\Provider;
use Symfony\Component\Form\Form;

class Template
{
    /**
     * @deprecated only for backward compatibility with previous versions of mobile
     * @var Message[]
     */
    public $messages = [];
    /**
     * @var array - form fields in symfony format
     */
    public $fields = [];
    /**
     * @var array
     */
    public $account;
    /**
     * @var \TAccountChecker
     */
    public $checker;
    /**
     * @var string
     */
    public $title;
    /**
     * @var Provider
     */
    public $provider;

    /**
     * extra js-code from engine/<provider>/form.js if there is such file exists.
     *
     * @var string[]
     */
    public $javaScripts = [];

    public static function getFieldTemplate($id, $type, $caption = null, $note = null, array $options = [])
    {
        $result = [
            'id' => $id,
            'type' => $type,
            'options' => array_merge(
                [
                    'attr' => [],
                    'constraints' => [],
                ],
                $options
            ),
        ];
        $class = '\\AwardWallet\\MainBundle\\Entity\\Account';
        $originalId = $id;

        if (property_exists($class, $id)) {
            $result['property'] = $id;
        } elseif (property_exists($class, $id = strtolower($originalId))) {
            $result['property'] = $id;
        } elseif (property_exists($class, $id = lcfirst($originalId))) {
            $result['property'] = $id;
        } else {
            $result['property'] = $originalId;
        }

        // Caption
        if (!empty($caption)) {
            $result['options']['label'] = $caption;
        } else {
            $result['options']['attr']['disableLabel'] = true;
        }

        // Note
        if (!empty($note)) {
            $result['options']['attr']['notice'] = $note;
        }

        return $result;
    }
}
