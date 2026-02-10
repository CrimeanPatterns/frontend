<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidget;

class PersonButtonWidget extends AbstractWidget
{
    protected $template;
    protected $params;

    protected $id;
    protected $link;
    protected $title;
    protected $count;
    protected $addLink;
    protected $addTitle;
    protected $isAllButton;
    protected $class;
    protected $clientId;
    protected $isActive;

    public function __construct($title, $link, $id, $count = null, $params = [])
    {
        parent::__construct();
        $this->template = 'left_menu_button.html.twig';
        $this->params = $params;

        $this->id = $id;
        $this->link = $link;
        $this->title = $title;
        $this->count = $count;
        $this->addLink = null;
        $this->addTitle = null;
        $this->isAllButton = false;
        $this->class = $params['class'] ?? null;
        $this->clientId = $params['clientId'] ?? null;

        $this->isActive = false;
    }

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $options['id'] = $this->id;
        $options['link'] = $this->link;
        $options['title'] = $this->title;
        $options['count'] = $this->count;
        $options['hasAdd'] = !empty($this->addLink);
        $options['addLink'] = $this->addLink;
        $options['addTitle'] = $this->addTitle;
        $options['isActive'] = $this->isActive;
        $options['isAllButton'] = $this->isAllButton;
        $options['clientId'] = $this->clientId;

        if ($this->class) {
            $options['class'] = $this->class;
        }

        return $this->container->get('twig')->render('@AwardWalletWidget/' . $this->template, $options);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return string
     */
    public function getAddLink()
    {
        return $this->addLink;
    }

    /**
     * @param string $addLink
     */
    public function setAddLink($addLink)
    {
        $this->addLink = $addLink;
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    public function setActive()
    {
        $this->setIsActive(true);
    }

    /**
     * @return bool
     */
    public function getIsAllButton()
    {
        return $this->isAllButton;
    }

    /**
     * @param bool $isAllButton
     */
    public function setIsAllButton($isAllButton)
    {
        $this->isAllButton = $isAllButton;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAddTitle()
    {
        return $this->addTitle;
    }

    /**
     * @param string $addTitle
     */
    public function setAddTitle($addTitle)
    {
        $this->addTitle = $addTitle;
    }
}
