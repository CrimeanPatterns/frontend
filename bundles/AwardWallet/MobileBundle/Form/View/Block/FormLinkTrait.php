<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

trait FormLinkTrait
{
    /**
     * @var string
     */
    public $formLink;

    /**
     * @var string
     */
    public $formTitle;

    /**
     * @param string $formLink
     * @return $this
     */
    public function setFormLink($formLink)
    {
        $this->formLink = $formLink;

        return $this;
    }

    /**
     * @return $this
     */
    public function setFormTitle($formTitle)
    {
        $this->formTitle = $formTitle;

        return $this;
    }
}
