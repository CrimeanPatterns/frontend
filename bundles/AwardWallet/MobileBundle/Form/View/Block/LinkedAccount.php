<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class LinkedAccount extends BaseBlock
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $provider;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $id;

    public function __construct()
    {
        $this->setType('linkedAccount');
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): LinkedAccount
    {
        $this->title = $title;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): LinkedAccount
    {
        $this->provider = $provider;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): LinkedAccount
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): LinkedAccount
    {
        $this->name = $name;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): LinkedAccount
    {
        $this->id = $id;

        return $this;
    }
}
