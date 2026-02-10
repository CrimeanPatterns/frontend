<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HotelPointValue.
 *
 * @ORM\Table(name="HotelPointValue", uniqueConstraints={@ORM\UniqueConstraint(name="ReservationID", columns={"ReservationID"})}, indexes={@ORM\Index(name="ProviderID", columns={"ProviderID"})})
 * @ORM\Entity
 */
class HotelPointValue
{
    /**
     * @var int
     * @ORM\Column(name="HotelPointValueID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="HotelName", type="string", length=250, nullable=false)
     */
    private $hotelName;

    /**
     * @var string|null
     * @ORM\Column(name="Address", type="string", length=250, nullable=true)
     */
    private $address;

    /**
     * @var string|null
     * @ORM\Column(name="LatLng", type="string", length=80, nullable=true)
     */
    private $latLng;

    /**
     * @var \DateTime
     * @ORM\Column(name="CheckInDate", type="date", nullable=false)
     */
    private $checkInDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CheckOutDate", type="date", nullable=false)
     */
    private $checkOutDate;

    /**
     * @var int
     * @ORM\Column(name="GuestCount", type="integer", nullable=false)
     */
    private $guestCount;

    /**
     * @var int
     * @ORM\Column(name="KidsCount", type="integer", nullable=false)
     */
    private $kidsCount;

    /**
     * @var int
     * @ORM\Column(name="RoomCount", type="integer", nullable=false)
     */
    private $roomCount;

    /**
     * @var string
     * @ORM\Column(name="Hash", type="string", length=32, nullable=false)
     */
    private $hash;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    private $updateDate;

    /**
     * @var string
     * @ORM\Column(name="TotalPointsSpent", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $totalPointsSpent;

    /**
     * @var string
     * @ORM\Column(name="TotalTaxesSpent", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $totalTaxesSpent;

    /**
     * @var string
     * @ORM\Column(name="AlternativeHotelName", type="string", length=250, nullable=false)
     */
    private $alternativeHotelName;

    /**
     * @var string
     * @ORM\Column(name="AlternativeHotelURL", type="string", length=512, nullable=false)
     */
    private $alternativeHotelUrl;

    /**
     * @var string
     * @ORM\Column(name="AlternativeBookingURL", type="string", length=512, nullable=false)
     */
    private $alternativeBookingUrl;

    /**
     * @var string|null
     * @ORM\Column(name="AlternativeLatLng", type="string", length=80, nullable=true)
     */
    private $alternativeLatLng;

    /**
     * @var string
     * @ORM\Column(name="AlternativeCost", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $alternativeCost;

    /**
     * @var string
     * @ORM\Column(name="PointValue", type="decimal", precision=10, scale=4, nullable=false)
     */
    private $pointValue;

    /**
     * @var string
     * @ORM\Column(name="Status", type="string", length=1, nullable=false, options={"default"="N","fixed"=true,"comment"="see CalcHotelPointValueCommand::STATUSES"})
     */
    private $status = 'N';

    /**
     * @var string|null
     * @ORM\Column(name="Note", type="string", length=500, nullable=true, options={"comment"="User-entered note"})
     */
    private $note;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID", nullable=false)
     * })
     */
    private $provider;

    /**
     * @var Reservation|null
     * @ORM\OneToOne(targetEntity="Reservation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ReservationID", referencedColumnName="ReservationID", nullable=true)
     * })
     */
    private $reservation;

    /**
     * @var HotelBrand|null
     * @ORM\OneToOne(targetEntity="HotelBrand")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="BrandID", referencedColumnName="HotelBrandID", nullable=true)
     * })
     */
    private $brand;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->updateDate = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHotelName(): string
    {
        return $this->hotelName;
    }

    /**
     * @return $this
     */
    public function setHotelName(string $hotelName): self
    {
        $this->hotelName = $hotelName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @return $this
     */
    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getLatLng(): ?string
    {
        return $this->latLng;
    }

    /**
     * @return $this
     */
    public function setLatLng(?string $latLng): self
    {
        $this->latLng = $latLng;

        return $this;
    }

    public function getCheckInDate(): \DateTime
    {
        return $this->checkInDate;
    }

    /**
     * @return $this
     */
    public function setCheckInDate(\DateTime $checkInDate): self
    {
        $this->checkInDate = $checkInDate;

        return $this;
    }

    public function getCheckOutDate(): \DateTime
    {
        return $this->checkOutDate;
    }

    /**
     * @return $this
     */
    public function setCheckOutDate(\DateTime $checkOutDate): self
    {
        $this->checkOutDate = $checkOutDate;

        return $this;
    }

    public function getGuestCount(): int
    {
        return $this->guestCount;
    }

    /**
     * @return $this
     */
    public function setGuestCount(int $guestCount): self
    {
        $this->guestCount = $guestCount;

        return $this;
    }

    public function getKidsCount(): int
    {
        return $this->kidsCount;
    }

    /**
     * @return $this
     */
    public function setKidsCount(int $kidsCount): self
    {
        $this->kidsCount = $kidsCount;

        return $this;
    }

    public function getRoomCount(): int
    {
        return $this->roomCount;
    }

    /**
     * @return $this
     */
    public function setRoomCount(int $roomCount): self
    {
        $this->roomCount = $roomCount;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return $this
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getCreateDate(): \DateTime
    {
        return $this->createDate;
    }

    /**
     * @return $this
     */
    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): \DateTime
    {
        return $this->updateDate;
    }

    /**
     * @return $this
     */
    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getTotalPointsSpent(): string
    {
        return $this->totalPointsSpent;
    }

    /**
     * @return $this
     */
    public function setTotalPointsSpent(string $totalPointsSpent): self
    {
        $this->totalPointsSpent = $totalPointsSpent;

        return $this;
    }

    public function getTotalTaxesSpent(): string
    {
        return $this->totalTaxesSpent;
    }

    /**
     * @return $this
     */
    public function setTotalTaxesSpent(string $totalTaxesSpent): self
    {
        $this->totalTaxesSpent = $totalTaxesSpent;

        return $this;
    }

    public function getAlternativeHotelName(): string
    {
        return $this->alternativeHotelName;
    }

    /**
     * @return $this
     */
    public function setAlternativeHotelName(string $alternativeHotelName): self
    {
        $this->alternativeHotelName = $alternativeHotelName;

        return $this;
    }

    public function getAlternativeHotelUrl(): string
    {
        return $this->alternativeHotelUrl;
    }

    /**
     * @return $this
     */
    public function setAlternativeHotelUrl(string $alternativeHotelUrl): self
    {
        $this->alternativeHotelUrl = $alternativeHotelUrl;

        return $this;
    }

    public function getAlternativeBookingUrl(): string
    {
        return $this->alternativeBookingUrl;
    }

    /**
     * @return $this
     */
    public function setAlternativeBookingUrl(string $alternativeBookingUrl): self
    {
        $this->alternativeBookingUrl = $alternativeBookingUrl;

        return $this;
    }

    public function getAlternativeLatLng(): ?string
    {
        return $this->alternativeLatLng;
    }

    /**
     * @return $this
     */
    public function setAlternativeLatLng(?string $alternativeLatLng): self
    {
        $this->alternativeLatLng = $alternativeLatLng;

        return $this;
    }

    public function getAlternativeCost(): ?string
    {
        return $this->alternativeCost;
    }

    /**
     * @return $this
     */
    public function setAlternativeCost(string $alternativeCost): self
    {
        $this->alternativeCost = $alternativeCost;

        return $this;
    }

    public function getPointValue(): string
    {
        return $this->pointValue;
    }

    /**
     * @return $this
     */
    public function setPointValue(string $pointValue): self
    {
        $this->pointValue = $pointValue;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @return $this
     */
    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    /**
     * @return $this
     */
    public function setProvider(Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    /**
     * @return $this
     */
    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getBrand(): ?HotelBrand
    {
        return $this->brand;
    }

    public function setBrand(?HotelBrand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }
}
