<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

/**
 * A class that converts a field value in JSON format to an array of strings.
 *
 * The class whose objects are to be encoded must implement a static method `getPropertiesArray()` that returns
 * an array of properties:
 * ```php
 * public static function getPropertiesArray()
 * {
 *     return ['name', 'code'];
 * }
 * ```
 */
class PropertiesArrayImploder extends AbstractBaseEncoder
{
    public function encode($input, EncoderContext $encoderContext)
    {
        return array_map(function ($entity) {
            $list = [];
            $class = get_class($entity);

            foreach (call_user_func([$class, 'getPropertiesArray']) as $property) {
                $method = 'get' . ucfirst($property);

                if (!method_exists($class, $method) || StringUtils::isEmpty($entity->$method())) {
                    continue;
                }

                $list[] = $entity->$method();
            }

            return implode(': ', $list);
        }, $input);
    }
}
