<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class UserInfo
{
    /**
     * @var string
     */
    private $email;

    /**
     * could be null if we requested only GMAIL scope.
     *
     * @var ?string
     */
    private $id;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

    /**
     * @var string|null
     */
    private $avatarURL;

    public function __construct(
        string $email,
        ?string $id,
        ?string $firstName,
        ?string $lastName,
        ?string $avatarURL = null
    ) {
        $this->email = $email;
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->avatarURL = $avatarURL;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getAvatarURL(): ?string
    {
        return $this->avatarURL;
    }
}
