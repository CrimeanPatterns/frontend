<?php

namespace AwardWallet\MainBundle\Service\Tripit;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class TripitImportResult
{
    private bool $success;
    private array $itineraries;
    private int $countAdded = 0;
    private int $countUpdated = 0;

    public function __construct(bool $success, array $itineraries = [])
    {
        $this->success = $success;
        $this->itineraries = $itineraries;
    }

    /**
     * Получить флаг, показывающий статус импорта резерваций.
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Получить список идентификаторов резерваций, которые были добавлены в наш сервис. В случае, если от API
     * ничего не пришло, вернётся пустой массив.
     */
    public function getItineraries(): array
    {
        return $this->itineraries;
    }

    /**
     * Get the number of added reservations.
     */
    public function getCountAdded(): int
    {
        return $this->countAdded;
    }

    public function setCountAdded(int $count): self
    {
        $this->countAdded = $count;

        return $this;
    }

    /**
     * Get the number of updated reservations.
     */
    public function getCountUpdated(): int
    {
        return $this->countUpdated;
    }

    public function setCountUpdated(int $count): self
    {
        $this->countUpdated = $count;

        return $this;
    }
}
