<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPointValue.
 *
 * @ORM\Table(name="UserPointValue", uniqueConstraints={@ORM\UniqueConstraint(name="ukeyUsrProvider", columns={"UserID", "ProviderID"})}, indexes={@ORM\Index(name="fkProviderID", columns={"ProviderID"}), @ORM\Index(name="IDX_C756B7B858746832", columns={"UserID"})})
 * @ORM\Entity
 */
class UserPointValue
{
    /**
     * @var int
     * @ORM\Column(name="UserPointValueID", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="Value", type="decimal", precision=10, scale=4, nullable=false, options={"comment"="Значение введенное пользователем"})
     */
    private $value;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updateDate;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    private $provider;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setUser(Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setProvider(Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    /**
     * @return $this
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return $this
     */
    public function setUpdateDate(\DateTime $updateDateTime): self
    {
        $this->updateDate = $updateDateTime;

        return $this;
    }

    public function getUpdateDate(): \DateTime
    {
        return $this->updateDate;
    }
}
