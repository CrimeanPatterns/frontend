<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Advertise;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Ad
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $priority;
    /**
     * @var string - url
     */
    public $image;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $link;

    /**
     * @var bool
     */
    public $visible;

    /**
     * Ad constructor.
     *
     * @param $id int
     * @param $image string
     * @param $title string
     * @param $description string
     * @param $link string
     * @param $visible boolean
     */
    public function __construct(int $id, int $priority, string $image, string $title, string $description, string $link, bool $visible)
    {
        $this->id = $id;
        $this->image = $image;
        $this->title = $title;
        $this->description = $description;
        $this->link = $link;
        $this->priority = $priority;
        $this->visible = $visible;
    }
}
