<?php

namespace AwardWallet\MainBundle\Service\Tripit\Converter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Tripit\Serializer\DateTimeObject;
use AwardWallet\Schema\Itineraries\Itinerary;

/**
 * @NoDI()
 */
abstract class BaseConverter
{
    /**
     * Конвертирует данные, пришедшие из API в объекты класса Itinerary.
     *
     * @return Itinerary
     */
    abstract public function run();

    /**
     * Переводит рекурсивно ассоциативный массив в объект.
     */
    protected function toObject(array $data)
    {
        $object = new $data['class']();

        foreach ($data['data'] as $key => $value) {
            if (strlen($key)) {
                if (is_array($value) && isset($value['data'])) {
                    $object->{$key} = $this->toObject($value);
                } else {
                    $object->{$key} = $value;
                }
            }
        }

        return $object;
    }

    /**
     * Получает число с плавающей точкой из строки, удаляя символ или название валюты.
     */
    protected function getNumberFromString(?string $value): float
    {
        $value = str_replace(',', '.', $value);

        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Получает строку с датой и временем в формате "Y-m-d\\TH:i:s" (RFC3339).
     *
     * @param DateTimeObject|null $date объект, содержащий дату, время и часовой пояс
     */
    protected function getDateTimeRFC3339(?DateTimeObject $date): ?string
    {
        if ($date instanceof DateTimeObject) {
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date->getDate() . ' ' . $date->getTime());

            if ($dateTime && $dateTime->format('Y-m-d H:i:s') === $date->getDate() . ' ' . $date->getTime()) {
                return $dateTime->format('Y-m-d\\TH:i:s');
            }
        }

        return null;
    }
}
