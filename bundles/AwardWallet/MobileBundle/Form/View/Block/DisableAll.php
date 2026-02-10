<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class DisableAll extends BaseBlock
{
    use FormLinkTrait;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $value;

    /**
     * @var array
     */
    public $attrs;

    public function __construct(
        string $name,
        bool $value,
        string $group,
        string $formLink,
        string $formTitle,
        array $attrs = ['formFieldName' => 'emailDisableAll']
    ) {
        $this->setType('disableAll');
        $this->setGroup($group);
        $this->setFormLink($formLink);
        $this->setFormTitle($formTitle);

        $this->name = $name;
        $this->value = $value;
        $this->attrs = $attrs;
    }
}
