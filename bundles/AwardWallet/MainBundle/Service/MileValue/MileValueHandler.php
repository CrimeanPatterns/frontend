<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Service\MileValue\Constants as MileValueConstants;
use Symfony\Contracts\Translation\TranslatorInterface;

class MileValueHandler
{
    private TranslatorInterface $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    public function formatter(string $key, array $item, bool $isFormatOnly = false): array
    {
        if (!array_key_exists($key, $item)) {
            $item[$key . '_currency'] = '';

            return $item;
        }

        $item[$key] = (float) $item[$key];
        $precision = ($item[$key] >= 0.0099 ? 2 : 4);
        $item[$key] = round($item[$key], $precision);

        $item[$key . '_currency'] = $this->translator->trans('us-cent-symbol');

        return $item;
    }

    public function getTypes(): array
    {
        return [
            MileValueConstants::ROUTE_TYPE_ONE_WAY =>
                $this->translator->trans('booking.request.add.form.segment.one', [], 'booking'),
            MileValueConstants::ROUTE_TYPE_ROUND_TRIP =>
                $this->translator->trans('booking.request.add.form.segment.round', [], 'booking'),
        ];
    }

    public function getClasses(): array
    {
        return [
            MileValueConstants::CLASS_ECONOMY => $this->translator->trans('economy'),
            MileValueConstants::CLASS_BUSINESS => $this->translator->trans('area.switcher.business'),
        ];
    }

    public function getType(string $type): array
    {
        $type = in_array($type, MileValueConstants::ROUTE_TYPES)
            ? $type
            : MileValueConstants::ROUTE_TYPE_ONE_WAY;

        return [
            'id' => $type,
            'name' => $this->getTypes()[$type],
        ];
    }

    public function getClass(string $class): array
    {
        $class = array_key_exists($class, $this->getClasses())
            ? $class
            : MileValueConstants::CLASS_ECONOMY;

        return [
            'id' => $class,
            'name' => $this->getClasses()[$class],
            'list' => in_array($class, MileValueConstants::ECONOMY_CLASSES)
                // ? MileValueConstants::ECONOMY_CLASSES
                ? MileValueService::CLASSOFSERVICE_ECONOMY
                : MileValueConstants::LUXE_CLASSES_OF_SERVICE,
        ];
    }
}
