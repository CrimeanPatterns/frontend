<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class TextProperty extends BaseBlock
{
    use FormLinkTrait;

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $text;
    /**
     * @var string
     */
    public $hint;
    /**
     * @var string
     */
    public $subHint;
    /**
     * @var array
     */
    public $attrs;
    /**
     * @var bool
     */
    public $new = false;

    /**
     * Text constructor.
     *
     * @param string $name
     * @param string $text
     * @param string $hint
     */
    public function __construct($name, $text = null, $hint = null, ?array $attrs = null)
    {
        $this->setType('textProperty');

        $this->name = $name;
        $this->text = $text;
        $this->hint = $hint;
        $this->attrs = $attrs;
    }

    public function setNew(bool $new): self
    {
        $this->new = $new;

        return $this;
    }
}
