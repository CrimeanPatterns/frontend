<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Lounge;

class LoungeListItemView extends AbstractBlockView
{
    public int $id;

    public string $name;

    public ?string $location;

    /**
     * @var AccessIconView[]
     */
    public ?array $access;

    public ?bool $isOpened;

    public ?int $nextEventTs;

    public ?ArrayBlockView $details;

    public ?array $blogLinks;

    public function __construct(
        Lounge $lounge,
        array $access,
        ?bool $isOpened = false,
        ?\DateTimeInterface $nextEvent = null,
        ?ArrayBlockView $details = null,
        ?array $blogLinks = null
    ) {
        parent::__construct('loungeListItem');
        $this->id = (int) $lounge->getId();
        $this->name = (string) $lounge->getName();
        $this->location = $lounge->getFinalLocation();
        $this->access = count($access) > 0 ? $access : null;
        $this->isOpened = $isOpened;

        if ($nextEvent) {
            $this->nextEventTs = $nextEvent->getTimestamp();
        } else {
            $this->nextEventTs = null;
        }

        $this->details = $details;
        $this->blogLinks = $blogLinks;
    }
}
