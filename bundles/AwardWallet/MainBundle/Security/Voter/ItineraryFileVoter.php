<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ItineraryFileVoter extends AbstractVoter
{
    protected ItineraryVoter $itineraryVoter;
    protected TimelineShareVoter $timelineShareVoter;

    public function __construct(
        ContainerInterface $container,
        ItineraryVoter $itineraryVoter,
        TimelineShareVoter $timelineShareVoter
    ) {
        parent::__construct($container);

        $this->itineraryVoter = $itineraryVoter;
        $this->timelineShareVoter = $timelineShareVoter;
    }

    public function edit(TokenInterface $token, ItineraryFile $file): bool
    {
        $user = $this->getBusinessUser($token);

        if (null === $user) {
            return false;
        }

        $kinds = array_flip(Itinerary::ITINERARY_KIND_TABLE);

        if (!array_key_exists($file->getItineraryTable(), $kinds)) {
            return false;
        }

        $itinerary = $file->getItinerary();

        if (is_null($itinerary)) {
            return false;
        }

        return $this->itineraryVoter->edit($token, $itinerary);
    }

    protected function getAttributes(): array
    {
        return [
            'EDIT' => [$this, 'edit'],
        ];
    }

    protected function getClass(): string
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Files\\ItineraryFile';
    }
}
