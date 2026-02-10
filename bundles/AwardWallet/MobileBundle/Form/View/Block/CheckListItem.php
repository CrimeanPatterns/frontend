<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class CheckListItem extends BaseBlock
{
    use FormLinkTrait;
    /**
     * @var string
     */
    public $name;
    /**
     * @var bool
     */
    public $checked;

    /**
     * @var array
     */
    public $attrs;

    /**
     * @var string
     */
    public $help;

    /**
     * ChecklistItem constructor.
     *
     * @param string $name
     * @param bool $checked
     */
    public function __construct($name, $checked, ?array $attrs = null, $help = null)
    {
        $this->setType('checklistItem');

        $this->name = $name;
        $this->checked = $checked;
        $this->attrs = $attrs;
        $this->help = $help;
    }
}
