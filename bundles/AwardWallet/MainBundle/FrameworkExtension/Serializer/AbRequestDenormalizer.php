<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Serializer;

use AwardWallet\MainBundle\Entity\AbSegment;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AbRequestDenormalizer extends ObjectNormalizer implements DenormalizerInterface
{
    /**
     * @throws UnexpectedValueException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $reflection = new \ReflectionClass($class);
        $roundTrip = $data['roundTrip'] ?? AbSegment::ROUNDTRIP_ROUND;
        $roundTripDaysIdeal = isset($data['roundTripDaysIdeal']) && is_numeric($data['roundTripDaysIdeal']) ? (int) $data['roundTripDaysIdeal'] : null;
        $roundTripDaysFlex = $roundTripDaysIdeal && isset($data['roundTripDaysFlex']) && is_bool($data['roundTripDaysFlex']) ? $data['roundTripDaysFlex'] : null;
        $roundTripDaysFrom = $roundTripDaysFlex && isset($data['roundTripDaysFrom']) && is_numeric($data['roundTripDaysFrom']) ? (int) $data['roundTripDaysFrom'] : null;
        $roundTripDaysTo = $roundTripDaysFlex && isset($data['roundTripDaysTo']) && is_numeric($data['roundTripDaysTo']) ? (int) $data['roundTripDaysTo'] : null;

        if (is_array($data)) {
            foreach ($data as $field => $value) {
                $key = ucfirst($field);

                if (!$reflection->hasProperty($key)) {
                    continue;
                }
                $doc = (new AnnotationReader())->getPropertyAnnotations($reflection->getProperty($key));

                if (isset($doc[0]->type) && $doc[0]->type == 'datetime') {
                    $data[$field] = \DateTime::createFromFormat('Y-m-d', $value);
                } elseif (isset($doc[0]->targetEntity) && is_array($value)) {
                    if ($doc[0]->targetEntity == 'AbSegment') {
                        switch ($roundTrip) {
                            case AbSegment::ROUNDTRIP_ROUND:
                            case AbSegment::ROUNDTRIP_ONEWAY:
                                $segment = $value[0];
                                $segment['roundTrip'] = $roundTrip;

                                if ($roundTrip == AbSegment::ROUNDTRIP_ROUND && $roundTripDaysIdeal) {
                                    $segment['roundTripDaysIdeal'] = (int) $roundTripDaysIdeal;
                                }

                                if ($roundTripDaysFlex) {
                                    $segment['roundTripDaysFlex'] = $roundTripDaysFlex;
                                }

                                if ($roundTripDaysFrom && $roundTripDaysTo) {
                                    $segment['roundTripDaysFrom'] = $roundTripDaysFrom;
                                    $segment['roundTripDaysTo'] = $roundTripDaysTo;
                                }
                                $value = [$segment];

                                break;

                            case AbSegment::ROUNDTRIP_MULTIPLE:
                                $value = array_map(function ($segment) {
                                    $segment['roundTrip'] = AbSegment::ROUNDTRIP_MULTIPLE;

                                    return $segment;
                                }, $value);

                                break;

                            default:
                                $value = [];
                        }
                    }

                    $data[$field] = array_map(function ($item) use ($doc) {
                        return $this->denormalize($item, 'AwardWallet\MainBundle\Entity\\' . $doc[0]->targetEntity);
                    }, $value);
                }
            }
        }

        return parent::denormalize($data, $class, $format, $context);
    }
}
