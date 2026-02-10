<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Security\OAuth\OAuthType;
use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\UserOAuth.
 *
 * @ORM\Table(name="UserOAuth")
 * @ORM\Entity()
 */
class UserOAuth
{
    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="FirstName", type="string", nullable=false)
     */
    protected $firstName;

    /**
     * @var string
     * @ORM\Column(name="LastName", type="string")
     */
    protected $lastName;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $declinedMailboxAccess = false;
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="UserOAuthID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="Provider", type="string", nullable=false)
     */
    private $provider;

    /**
     * @var string
     * @ORM\Column(name="OAuthID", type="string", nullable=false)
     */
    private $providerUserId;

    /**
     * @var string
     * @ORM\Column(name="AvatarURL", type="string", nullable=true)
     */
    private $avatarURL;

    /**
     * @var string
     * @ORM\Column(name="lastLoginDate", type="datetime", nullable=false)
     */
    private $lastLoginDate;

    public function __construct(
        Usr $user,
        string $email,
        string $firstName,
        ?string $lastName,
        string $provider,
        string $providerUserId,
        ?string $avatarURL = null
    ) {
        if (!in_array($provider, [OAuthType::GOOGLE, OAuthType::YAHOO, OAuthType::AOL, OAuthType::MICROSOFT, OAuthType::APPLE])) {
            throw new \InvalidArgumentException("unknown oauth provider: {$provider}");
        }

        $this->user = $user;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->provider = $provider;
        $this->providerUserId = $providerUserId;
        $this->avatarURL = $avatarURL;
        $this->lastLoginDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setAvatarURL(?string $avatarURL): self
    {
        $this->avatarURL = $avatarURL;

        return $this;
    }

    public function getAvatarURL(): ?string
    {
        return $this->avatarURL;
    }

    public function setLastLoginDate(\DateTimeInterface $dateTime): self
    {
        $this->lastLoginDate = $dateTime;

        return $this;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): UserOAuth
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): UserOAuth
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): UserOAuth
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function getProviderUserId(): string
    {
        return $this->providerUserId;
    }

    public function isDeclinedMailboxAccess(): bool
    {
        return $this->declinedMailboxAccess;
    }

    public function setDeclinedMailboxAccess(bool $declinedMailboxAccess): self
    {
        $this->declinedMailboxAccess = $declinedMailboxAccess;

        return $this;
    }
}
