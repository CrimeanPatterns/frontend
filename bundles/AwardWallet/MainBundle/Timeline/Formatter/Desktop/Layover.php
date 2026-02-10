<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\DesktopFormatterFactory;
use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\MainBundle\Timeline\Formatter\Origin;
use AwardWallet\MainBundle\Timeline\Formatter\Utils\ParkingHeaderResolver;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\AirLayover;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\Layover as LayoverItem;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Layover extends AbstractLayover
{
    private Finder $loungeFinder;

    public function __construct(
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        DateTimeIntervalFormatter $intervalFormatter,
        DesktopFormatterFactory $desktopFormatterFactory,
        Origin $originFormatter,
        ParkingHeaderResolver $parkingHeaderResolver,
        Finder $loungeFinder
    ) {
        parent::__construct($localizeService, $translator, $urlGenerator, $authorizationChecker, $tokenStorage, $intervalFormatter, $desktopFormatterFactory, $originFormatter, $parkingHeaderResolver);

        $this->loungeFinder = $loungeFinder;
    }

    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        if ($item instanceof AirLayover && !empty($airportCode = $item->getAirportCode())) {
            $result['lounges'] = $this->loungeFinder->getNumberAirportLounges($airportCode);
        }

        return $result;
    }

    /**
     * @param LayoverItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        return $this->translator->trans(
            /** @Desc("<strong>%duration%</strong> Layover<gray>@</gray>%location%") */
            'duration-layover-at',
            $this->transParams([
                '%duration%' => $this->intervalFormatter->formatDurationViaInterval($item->getDuration()),
                '%location%' => $item->getLocation(),
            ]),
            'trips'
        );
    }

    protected function getDetails(AbstractItineraryItem $item): array
    {
        return [];
    }

    protected function getDetailsOrder(): array
    {
        return [];
    }
}
