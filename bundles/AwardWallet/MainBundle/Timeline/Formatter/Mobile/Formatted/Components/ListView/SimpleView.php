<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView;

class SimpleView
{
    /**
     * @var string
     */
    public $kind = 'simple';

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $val;

    /**
     * SimpleView constructor.
     *
     * @param string $title
     * @param string $val
     */
    public function __construct($title, $val)
    {
        $this->title = $title;
        $this->val = $val;
    }
}
