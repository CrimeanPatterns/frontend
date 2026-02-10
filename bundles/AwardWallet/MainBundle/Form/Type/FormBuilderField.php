<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\FormTypeInterface;

class FormBuilderField
{
    /**
     * @var string|FormTypeInterface    The type of the form or null if name is a property
     */
    public $type;
    /**
     * @var array
     */
    public $options;

    /**
     * @param string|FormTypeInterface $type
     */
    public function __construct($type = null, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
    }
}
