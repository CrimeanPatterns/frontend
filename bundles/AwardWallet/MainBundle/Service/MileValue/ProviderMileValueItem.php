<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @NoDI()
 */
class ProviderMileValueItem
{
    public const LIST_KEY_VALUES = [
        'AvgPointValue',
        'RegionalEconomyMileValue',
        'RegionalBusinessMileValue',
        'GlobalEconomyMileValue',
        'GlobalBusinessMileValue',
    ];

    public const MIN_COUNT = 5;
    public const CURRENCY_CENT = '¢';

    private $providerId;
    private $kind;
    private $code;
    private $name;
    private $shortName;
    private $certifyDate;

    private $autoValues;
    private $manualValues;
    private $userValues;
    private $simulateValues = [];
    private $subBrands;

    public function __construct()
    {
        $this->autoValues =
        $this->manualValues =
        $this->userValues =
        $this->simulateValues = [];
    }

    public function getTitle(string $key, bool $isPrimaryValue, TranslatorInterface $translator, LocalizeService $localizeService): string
    {
        [$isUser, $isManual, $isAuto, $isSimulate] = array_values($this->fetchAvailableValues($key));

        if ($isUser && $isPrimaryValue) {
            return $translator->trans('personally-set-average');
        }

        if (!$isUser && !$isPrimaryValue) {
            return '';
        }

        $certifyDate = '';

        if (!$isManual && $isAuto && $this->autoValues[$key]['isNotEnoughData']) {
            return $translator->trans('not-enough-data');
        }

        if (!$isManual && $isAuto) {
            if (!empty($this->certifyDate)) {
                $date = strtotime($this->certifyDate);
                $certifyDate = ', ' . $translator->trans('as-of-date', ['%date%' => $localizeService->formatDate(new \DateTime('@' . $date))]);
            }

            if (!empty($this->autoValues[$key]['count'])) {
                return $translator->trans(
                    'based-on-last-bookings',
                    [
                        '%number%' => $localizeService->formatNumberWithFraction($this->autoValues[$key]['count'], 0),
                        '%as-of-date%' => $certifyDate,
                    ]);
            }
        }

        if ($isManual) {
            return $translator->trans('manually_set_by_aw') . $certifyDate;
        }

        return '';
    }

    public function getPrimary(string $key): object
    {
        [$isUser, $isManual, $isAuto, $isSimulate] = array_values($this->fetchAvailableValues($key));

        $result = [
            'value' => $this->getPrimaryValue($key),
            // 'title' => $this->getTitle($key, true),
            'isUser' => $this->isUserPrimary($key),
            'isNotEnoughData' => !$isUser && !$isManual && $isAuto ? $this->autoValues[$key]['isNotEnoughData'] : false,
            'isSimulate' => $isSimulate,
        ];

        return (object) $result;
    }

    public function getPrimaryValue(string $key): ?float
    {
        if ($this->isUserPrimary($key)) {
            return $this->userValues[$key]['value'];
        }

        return $this->getValue($key);
    }

    public function isUserPrimary(string $key): bool
    {
        return array_key_exists($key, $this->userValues);
    }

    public function getSecondary(string $key): ?object
    {
        [$isUser, $isManual, $isAuto] = array_values($this->fetchAvailableValues($key));

        if (!$isUser) {
            return null;
        }

        $value = $this->getSecondaryValue($key);

        if (null === $value) {
            return null;
        }

        $result = [
            'value' => $value,
            // 'title' => $this->getTitle($key, false),
            'isUser' => false,
            'isNotEnoughData' => !$isManual && $isAuto ? $this->autoValues[$key]['isNotEnoughData'] : false,
        ];

        return (object) $result;
    }

    public function getSecondaryValue(string $key): ?float
    {
        [$isUser, $isManual, $isAuto] = array_values($this->fetchAvailableValues($key));

        if (!$isUser) {
            return null;
        }

        return $this->getValue($key);
    }

    public function isEmptyData(): bool
    {
        return empty($this->autoValues)
            && empty($this->manualValues)
            && empty($this->userValues)
            && empty($this->simulateValues);
    }

    public function isNotEnoughData(string $key)
    {
        if ($this->isUserPrimary($key)) {
            return false;
        }

        return $this->getPrimary($key)->isNotEnoughData;
    }

    public function extractItemData(string $keyData, $item): self
    {
        $this->providerId = $item['ProviderID'];
        $this->name = rtrim($item['DisplayName'] ?? $item['Name'], '*');

        if (!empty($item['CertificationDate'])) {
            $this->certifyDate = $item['CertificationDate'];
        }

        if (!empty($item['ShortName'])) {
            $this->setShortName($item['ShortName']);
        }

        if (!empty($item['Kind'])) {
            $this->setKind($item['Kind']);
        }

        if (!empty($item['Code'])) {
            $this->setCode($item['Code']);
        }

        foreach (self::LIST_KEY_VALUES as $key) {
            if ($key !== $keyData) {
                continue;
            }

            if (!empty($item['_count'])) {
                $this->setAutoValues($keyData, $item['sumAlternativeCost'], $item['sumTaxesSpent'], $item['sumSpent'], $item['_count']);
            }

            if (!empty($item[$key])) {
                if (!array_key_exists($key, $this->manualValues)) {
                    $this->manualValues[$key] = [];
                }

                $this->manualValues[$key]['value'] = $this->cutValue($item[$key]);
                $this->manualValues[$key]['currency'] = self::CURRENCY_CENT;
            }
        }

        if (!empty($item['brands'])) {
            $brands = [];

            foreach ($item['brands'] as $brand) {
                $brands[] = (new ProviderMileValueItem())
                    ->extractItemData($keyData, $brand)
                    ->setProviderId($brand['HotelBrandID']);
            }

            usort(
                $brands,
                static function ($a, $b) {
                    $valA = $a->getAutoValues()['AvgPointValue']['value'];
                    $valB = $b->getAutoValues()['AvgPointValue']['value'];

                    if ($a->getAutoValues()['AvgPointValue']['isNotEnoughData']) {
                        $valA = 0;
                    }

                    if ($b->getAutoValues()['AvgPointValue']['isNotEnoughData']) {
                        $valB = 0;
                    }

                    return ($valA > $valB) ? -1 : 1;
                });

            $this->subBrands = $brands;
        }

        return $this;
    }

    public function setUserValues(array $data): self
    {
        foreach (self::LIST_KEY_VALUES as $keyData) {
            foreach ($data as $key => $value) {
                if ($keyData !== $key || !MileValueService::isValidValue($value)) {
                    continue;
                }

                if (!array_key_exists($key, $this->userValues)) {
                    $this->userValues[$key] = [];
                }

                $this->userValues[$key]['value'] = $value;
                $this->userValues[$key]['currency'] = $data[$key . '_currency'] ?? self::CURRENCY_CENT;
            }
        }

        return $this;
    }

    public function setSimulateValues(array $data): self
    {
        foreach (self::LIST_KEY_VALUES as $keyData) {
            foreach ($data as $key => $value) {
                if ($keyData !== $key) {
                    continue;
                }

                if (!array_key_exists($key, $this->simulateValues)) {
                    $this->simulateValues[$key] = [];
                }

                $this->simulateValues[$key]['value'] = $value;
                $this->simulateValues[$key]['currency'] = $data[$key . '_currency'] ?? self::CURRENCY_CENT;
            }
        }

        return $this;
    }

    public function setRangeValues(string $key, ?float $minValue, ?float $maxValue): self
    {
        $this->autoValues[$key]['minValue'] = $minValue;
        $this->autoValues[$key]['maxValue'] = $maxValue;

        return $this;
    }

    /**
     * The custom value was added only by the user. There are no other values.
     */
    public function isUserOnly(): bool
    {
        return empty($this->autoValues) && empty($this->manualValues);
    }

    public function isManualValuesExists(string $key): bool
    {
        return array_key_exists($key, $this->manualValues);
    }

    public function isSimulateExists(string $key): bool
    {
        return array_key_exists($key, $this->simulateValues);
    }

    public function formatValue(?float $value): string
    {
        if ($value > 0) {
            return $this->cutValue($value) . ' ' . self::CURRENCY_CENT;
        }

        return '';
    }

    public function setProviderId(int $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    public function setKind(?int $kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    public function getKind(): ?int
    {
        return $this->kind;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return html_entity_decode($this->name);
    }

    public function setCertifyDate(?string $certifyDate): self
    {
        $this->certifyDate = $certifyDate;

        return $this;
    }

    public function getCertifyDate(): ?string
    {
        return $this->certifyDate;
    }

    public function setShortName(?string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getShortName(): ?string
    {
        return html_entity_decode($this->shortName);
    }

    public function getAutoValues(): array
    {
        return $this->autoValues;
    }

    public function getManualValues(): array
    {
        return $this->manualValues;
    }

    public function getUserValues(): array
    {
        return $this->userValues;
    }

    public function getSimulateValues(): array
    {
        return $this->simulateValues;
    }

    public function getSubBrands(): ?array
    {
        return $this->subBrands;
    }

    public function getMinValue(string $key): ?float
    {
        return $this->autoValues[$key]['minValue'] ?? null;
    }

    public function getMaxValue(string $key): ?float
    {
        return $this->autoValues[$key]['maxValue'] ?? null;
    }

    private function fetchAvailableValues(string $key): array
    {
        return [
            'user' => array_key_exists($key, $this->userValues),
            'manual' => array_key_exists($key, $this->manualValues),
            'auto' => array_key_exists($key, $this->autoValues),
            'simulate' => array_key_exists($key, $this->simulateValues),
        ];
    }

    private function getValue(string $key): ?float
    {
        if (array_key_exists($key, $this->manualValues)) {
            return $this->manualValues[$key]['value'] ?? null;
        }

        if (array_key_exists($key, $this->autoValues)) {
            return $this->autoValues[$key]['value'] ?? null;
        }

        if (array_key_exists($key, $this->simulateValues)) {
            return $this->simulateValues[$key]['value'] ?? null;
        }

        return null;
    }

    private function setAutoValues(string $key, $sumAlternativeCost, $sumTaxesSpent, $sumSpent, $count): self
    {
        if (!array_key_exists($key, $this->autoValues)) {
            $this->autoValues[$key] = [];
        }

        $this->autoValues[$key]['value'] = $this->cutValue(MileValueCalculator::calc($sumAlternativeCost, $sumTaxesSpent, $sumSpent));
        $this->autoValues[$key]['count'] = $count;
        $this->autoValues[$key]['sumAlternativeCost'] = $sumAlternativeCost;
        $this->autoValues[$key]['sumTaxesSpent'] = $sumTaxesSpent;
        $this->autoValues[$key]['sumSpent'] = $sumSpent;
        $this->autoValues[$key]['currency'] = self::CURRENCY_CENT;

        $this->autoValues[$key]['isNotEnoughData'] = (int) $count < self::MIN_COUNT;

        return $this;
    }

    private function cutValue($value)
    {
        return round($value, $value >= 0.0099 ? 2 : 4);
    }
}
