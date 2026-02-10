<?php

namespace AwardWallet\MainBundle\Service\Tripit;

use AwardWallet\MainBundle\Service\Tripit\Serializer\ProfileEmailAddressObject;
use AwardWallet\Schema\Itineraries\Itinerary;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TripitConverter
{
    /**
     * Список свойств, которые необходимо приводить к виду многомерного массива.
     */
    private const NOT_MULTI_PROPERTY_NAMES = ['Guest', 'Participant', 'ReservationHolder', 'Segment', 'Traveler'];
    private const NAMESPACE_SERIALIZERS = 'AwardWallet\MainBundle\Service\Tripit\Serializer';

    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * Массив, ключи которого являются резервациями, которые приходят из API TripIt, а значения — это классы,
     * которые конвертируют данные в объекты Itineraries.
     *
     * @return string[]
     */
    public function objects(): array
    {
        return [
            'ActivityObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Activity::class,
            'AirObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Air::class,
            'CarObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Car::class,
            'CruiseObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Cruise::class,
            'LodgingObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Lodging::class,
            'ParkingObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Parking::class,
            'RailObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Rail::class,
            'RestaurantObject' => \AwardWallet\MainBundle\Service\Tripit\Converter\Restaurant::class,
            'TransportObject' => [
                'F' => [
                    'serializer' => 'FerryObject',
                    'converter' => \AwardWallet\MainBundle\Service\Tripit\Converter\Ferry::class,
                ],
                'G' => [
                    'serializer' => 'BusObject',
                    'converter' => \AwardWallet\MainBundle\Service\Tripit\Converter\Bus::class,
                ],
            ],
        ];
    }

    /**
     * Конвертирует полученные резервации от API.
     *
     * @return array|Itinerary[] возвращает массив объектов `Itinerary`, либо пустой массив, если предстоящие
     * резервации отсутствуют
     */
    public function convert(array $data): array
    {
        $result = [];

        foreach ($this->objects() as $field => $className) {
            if (isset($data[$field])) {
                if ($this->isMultidimensional($data[$field])) {
                    foreach ($data[$field] as $trip) {
                        $itinerary = $this->getItinerary($trip, $field, $className);

                        if ($itinerary !== null) {
                            $result[] = $itinerary;
                        }
                    }
                } else {
                    $itinerary = $this->getItinerary($data[$field], $field, $className);

                    if ($itinerary !== null) {
                        $result[] = $itinerary;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Подсчитывает количество дублирующихся резерваций по коду подтверждения.
     *
     * @return array возвращает массив типов резерваций и количество дубликатов для каждого из них
     */
    public function findDuplicates(array $data): array
    {
        $result = [];
        $confirmationCodeFields = [
            'ActivityObject' => 'booking_site_conf_num',
            'AirObject' => 'supplier_conf_num',
            'CarObject' => 'supplier_conf_num',
            'CruiseObject' => 'booking_site_conf_num',
            'LodgingObject' => 'supplier_conf_num',
            'ParkingObject' => 'supplier_conf_num',
            'RailObject' => 'booking_site_conf_num',
            'RestaurantObject' => 'supplier_conf_num',
            'TransportObject' => 'supplier_conf_num',
        ];

        foreach ($this->objects() as $field => $className) {
            if (!isset($data[$field])) {
                continue;
            }

            $confirmationNumbers = [];

            if ($this->isMultidimensional($data[$field])) {
                foreach ($data[$field] as $trip) {
                    if (!isset($trip[$confirmationCodeFields[$field]])) {
                        continue;
                    }

                    $number = $trip[$confirmationCodeFields[$field]];

                    if (!isset($confirmationNumbers[$number])) {
                        $confirmationNumbers[$number] = 1;
                    } else {
                        $confirmationNumbers[$number]++;
                    }
                }
            }

            $duplicates = array_filter($confirmationNumbers, function ($value) {
                return $value > 1;
            });

            if (count($duplicates) !== 0) {
                $result[$field] = count($duplicates);
            }
        }

        return $result;
    }

    /**
     * Конвертирует данные учётной записи в объект `Profile`.
     */
    public function convertProfile(array $data): ?ProfileEmailAddressObject
    {
        if (!isset($data['Profile']['ProfileEmailAddresses'])) {
            return null;
        }

        if (!$this->isMultidimensional($data['Profile']['ProfileEmailAddresses']['ProfileEmailAddress'])) {
            $data['Profile']['ProfileEmailAddresses'] = array_values($data['Profile']['ProfileEmailAddresses']);
        } else {
            $data['Profile']['ProfileEmailAddresses'] = $data['Profile']['ProfileEmailAddresses']['ProfileEmailAddress'];
        }

        /** @var \AwardWallet\MainBundle\Service\Tripit\Serializer\ProfileObject $profile */
        $profile = $this->serializer->deserialize(json_encode($data['Profile']), self::NAMESPACE_SERIALIZERS . '\\ProfileObject', 'json');

        foreach ($profile->getProfileEmailAddresses() as $address) {
            /** @var ProfileEmailAddressObject $address */
            if ($address->getIsPrimary()) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Логирует все объекты резерваций, которые приходят от API.
     */
    public function logResponse(array $data, TripitUser $user): void
    {
        foreach ($this->objects() as $field => $className) {
            if (!isset($data[$field])) {
                continue;
            }

            if ($this->isMultidimensional($data[$field])) {
                foreach ($data[$field] as $trip) {
                    if (isset($trip['notes'])) {
                        unset($trip['notes']);
                    }

                    $this->logger->info('TripIt list: ' . json_encode([
                        'userId' => $user->getCurrentUser()->getId(),
                        'type' => $field,
                        'response' => $trip,
                    ]));
                }
            } else {
                if (isset($data[$field]['notes'])) {
                    unset($data[$field]['notes']);
                }

                $this->logger->info('TripIt list: ' . json_encode([
                    'userId' => $user->getCurrentUser()->getId(),
                    'type' => $field,
                    'response' => $data[$field],
                ]));
            }
        }
    }

    /**
     * Получить экземпляр Itinerary.
     *
     * @param array $trip данные о резервации, пришедшие в ответе от API
     * @param string $type имя класса сериалайзера, который переводит json в объект
     * @param string|array $className имя класса конвертера, который генерирует резервацию
     * @return Itinerary|null возвращает экземпляр класса Itinerary, либо 'null' в случае, если валидация
     * обязательных параметров не была пройдена
     */
    private function getItinerary(array $trip, string $type, $className)
    {
        // Если в резервации имеется один путешественник, то из API вернётся один объект, а если два и больше
        // то массив объектов. Для единообразия всегда приводим к многомерному массиву.
        foreach (self::NOT_MULTI_PROPERTY_NAMES as $property) {
            if (isset($trip[$property]) && !$this->isMultidimensional($trip[$property])) {
                $trip[$property] = [$trip[$property]];
            }
        }

        // Резервации типа "Ground Transportation" и "Ferry" приходят в одном объекте, у них различается только
        // значение свойства `detail_type_code`. У "Transportation" это свойство вообще отсутствует.
        if (is_array($className)) {
            if (!isset($trip['Segment'][0]['detail_type_code'])) {
                $trip['Segment'][0]['detail_type_code'] = 'G';
            }

            $type = $className[$trip['Segment'][0]['detail_type_code']]['serializer'];
            $className = $className[$trip['Segment'][0]['detail_type_code']]['converter'];
        }

        $object = $this->serializer->deserialize(json_encode($trip), self::NAMESPACE_SERIALIZERS . '\\' . $type, 'json');
        $errors = $this->validator->validate($object);

        if (count($errors) > 0) {
            $result = [];

            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $result[$error->getPropertyPath()][] = $error->getMessage();
            }

            $this->logger->info('TripIt converter: ' . json_encode([
                'tripId' => $object->getTripId(),
                'type' => $type,
                'errors' => $result,
            ]));

            return null;
        }

        return (new $className($object))->run();
    }

    /**
     * Проверяет, что переданный массив является многомерным.
     */
    private function isMultidimensional(array $array): bool
    {
        return is_array($array[array_key_first($array)]) && array_key_first($array) === 0;
    }
}
