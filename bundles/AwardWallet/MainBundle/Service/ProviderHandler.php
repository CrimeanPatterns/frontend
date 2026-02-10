<?php

namespace AwardWallet\MainBundle\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderHandler
{
    public const PROVIDER_KIND_TRANSFERS = 100;

    public const KIND_KEYS = [
        PROVIDER_KIND_AIRLINE => 'airlines',
        PROVIDER_KIND_HOTEL => 'hotels',
        PROVIDER_KIND_CREDITCARD => 'banks',
        PROVIDER_KIND_SHOPPING => 'shops',
        PROVIDER_KIND_CAR_RENTAL => 'rent',
        PROVIDER_KIND_DINING => 'dinings',
        PROVIDER_KIND_TRAIN => 'trains',
        PROVIDER_KIND_CRUISES => 'cruises',
        PROVIDER_KIND_SURVEY => 'surveys',
        PROVIDER_KIND_PARKING => 'parking',
        PROVIDER_KIND_OTHER => 'others',
    ];

    public const KIND_KEYS_EXTEND = [
        self::PROVIDER_KIND_TRANSFERS => 'transfers',
    ];

    public const KINDS_LIST = [
        PROVIDER_KIND_AIRLINE => 'track.group.airline',
        PROVIDER_KIND_HOTEL => 'track.group.hotel',
        PROVIDER_KIND_CREDITCARD => 'track.group.card',
        PROVIDER_KIND_SHOPPING => 'track.group.shop',
        PROVIDER_KIND_CAR_RENTAL => 'track.group.rent',
        PROVIDER_KIND_DINING => 'track.group.dining',
        PROVIDER_KIND_TRAIN => 'track.group.train',
        PROVIDER_KIND_CRUISES => 'track.group.cruise',
        PROVIDER_KIND_SURVEY => 'track.group.survey',
        PROVIDER_KIND_PARKING => 'track.group.parking',
        PROVIDER_KIND_OTHER => 'track.group.other',
    ];
    public const KINDS_LIST_EXTEND = [
        self::PROVIDER_KIND_TRANSFERS => 'transferable-points',
    ];

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getLocalizedList(): array
    {
        $result = [];

        foreach (self::KINDS_LIST as $kind => $translationKey) {
            $result[$kind] = $this->translator->trans($translationKey);
        }

        return $result;
    }

    public function getKinds(): array
    {
        return array_keys(self::KINDS_LIST);
    }

    public function getLocalizedKind(int $kind): ?string
    {
        return $this->getLocalizedList()[$kind] ?? null;
    }
}
