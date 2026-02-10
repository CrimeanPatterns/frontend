<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Features\FeaturesMap;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem as PMVItem;
use AwardWallet\MainBundle\Service\ProviderHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileMileValueFormatter
{
    private const FEATURE_SHORT_LIST = 'short_list';
    private const FEATURE_SHOW_BRANDS = 'show_brands';

    private LocalizeService $localizer;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ApiVersioningService $apiVersioningService;
    private TranslatorInterface $translator;

    public function __construct(
        LocalizeService $localizer,
        AuthorizationCheckerInterface $authorizationChecker,
        ApiVersioningService $apiVersioningService,
        TranslatorInterface $translator
    ) {
        $this->localizer = $localizer;
        $this->authorizationChecker = $authorizationChecker;
        $this->apiVersioningService = $apiVersioningService;
        $this->translator = $translator;
    }

    public function formatList(array $data): array
    {
        $features = new FeaturesMap([
            self::FEATURE_SHOW_BRANDS => $this->authorizationChecker->isGranted('ROLE_STAFF'),
            self::FEATURE_SHORT_LIST => false,
        ]);

        return
            it($data)
            ->filter(fn (array $kindData) => $kindData['data'] ?? [])
            ->map(fn (array $kindData): array => $this->formatMileValueKind($kindData, $features))
            ->toArray();
    }

    public function formatShortList(array $data): array
    {
        $features = new FeaturesMap([
            self::FEATURE_SHOW_BRANDS => false,
            self::FEATURE_SHORT_LIST => true,
        ]);

        $banks = $data[ProviderHandler::KIND_KEYS[PROVIDER_KIND_CREDITCARD]]['data'] ?? [];
        $transfers = $data[ProviderHandler::KIND_KEYS_EXTEND[ProviderHandler::PROVIDER_KIND_TRANSFERS]]['data'] ?? [];

        $order = [
            Provider::CHASE_ID,
            Provider::AMEX_ID,
            Provider::CAPITAL_ONE_ID,
            Provider::CITI_ID,
            Provider::BANKOFAMERICA_ID,
            Provider::BREX_ID,
            Provider::BARCLAYCARD_ID,
            Provider::BILT_REWARDS_ID,
            Provider::WELLSFARGO_ID,
        ];
        $ordered = [];

        foreach ($order as $providerId) {
            if (array_key_exists($providerId, $transfers)) {
                $ordered[$providerId] = $transfers[$providerId];
                unset($transfers[$providerId]);
            } elseif (array_key_exists($providerId, $banks)) {
                $ordered[$providerId] = $banks[$providerId];
            }
        }

        return
            it(array_merge($ordered, $transfers))
            ->map(fn (PMVItem $rowData) => $this->formatMileValueRow($rowData, $features))
            ->toArray();
    }

    protected function formatMileValueKind(array $kindData, FeaturesMap $features): array
    {
        $title = array_key_exists('titleTranslateId', $kindData)
            ? $this->translator->trans($kindData['titleTranslateId'])
            : $kindData['title'];

        return [
            'title' => $title,
            'data' =>
                it($kindData['data'])
                ->map(fn (PMVItem $datum): array => $this->formatMileValueRow($datum, $features))
                ->toArray(),
        ];
    }

    protected function formatMileValueRow(PMVItem $rowData, FeaturesMap $features): array
    {
        $result = [
            'name' => \html_entity_decode(
                $features->supports(self::FEATURE_SHORT_LIST) ?
                    $rowData->getShortName() :
                    $rowData->getName()
            ),
            'value' => [
                'primary' => $this->formatValueAll(
                    $rowData->isNotEnoughData('AvgPointValue') ?
                        0.0 :
                        $rowData->getPrimaryValue('AvgPointValue'),
                    PMVItem::CURRENCY_CENT
                ),
            ],
            'custom' => $rowData->isUserOnly(),
            'flightClass' => [],
            'id' => $rowData->getProviderId(),
            'kind' => $rowData->getKind(),
        ];

        if ($features->supports(self::FEATURE_SHOW_BRANDS)) {
            $result['brands'] =
                it($rowData->getSubBrands() ?? [])
                ->map(fn (PMVItem $item) => $this->formatBrand($item))
                ->toArray();
        }

        if ($this->apiVersioningService->supports(MobileVersions::MILE_VALUE_PROVIDER_CODE)) {
            $result['code'] = $rowData->getCode();
        }

        $secondary = $rowData->getSecondaryValue('AvgPointValue');

        if (null !== $secondary && !$rowData->isNotEnoughData('AvgPointValue')) {
            $result['value']['secondary'] = $this->formatValueAll(
                $secondary,
                PMVItem::CURRENCY_CENT
            );
        }

        if ($features->notSupports(self::FEATURE_SHORT_LIST)) {
            $economy = $this->formatFlightClass($rowData, 'RegionalEconomyMileValue', 'GlobalEconomyMileValue');

            if ($economy) {
                $result['flightClass']['economy'] = $economy;
            }

            $business = $this->formatFlightClass($rowData, 'RegionalBusinessMileValue', 'GlobalBusinessMileValue');

            if ($business) {
                $result['flightClass']['business'] = $business;
            }
        }

        return $result;
    }

    protected function formatFlightClass(PMVItem $item, string $regionalKey, string $globalKey): ?array
    {
        $hasRegional =
            ((($regional = $item->getPrimaryValue($regionalKey)) ?? 0) > 0)
            && !$item->isNotEnoughData($regionalKey);
        $hasGlobal =
            ((($global = $item->getPrimaryValue($globalKey)) ?? 0) > 0)
            && !$item->isNotEnoughData($globalKey);

        if ($hasRegional || $hasGlobal) {
            return [
                'global' => $hasGlobal ?
                    ($this->formatValue(
                        $global,
                        PMVItem::CURRENCY_CENT
                    )) :
                    'n/a',
                'regional' => $hasRegional ?
                    ($this->formatValue(
                        $regional,
                        PMVItem::CURRENCY_CENT
                    )) :
                    'n/a',
            ];
        }

        return null;
    }

    protected function formatValue(float $value, string $currency): string
    {
        if ($value > 0) {
            return $this->localizer->formatNumberWithFraction($value, 2) . $currency;
        }

        return '';
    }

    protected function formatValueAll(float $value, string $currency): array
    {
        if ($value > 0) {
            $formatted = $this->localizer->formatNumberWithFraction($value, 2);

            return [
                'value' => $formatted . $currency,
                'raw' => $formatted,
            ];
        } else {
            return [
                'value' => 'n/a',
                'raw' => '',
            ];
        }
    }

    private function formatBrand(PMVItem $item): array
    {
        return [
            'name' => \html_entity_decode($item->getName()),
            'value' => [
                'primary' => $this->formatValueAll(
                    $item->isNotEnoughData('AvgPointValue') ?
                        0.0 :
                        $item->getPrimaryValue('AvgPointValue'),
                    PMVItem::CURRENCY_CENT
                ),
            ],
            'brandId' => $item->getProviderId(),
        ];
    }
}
