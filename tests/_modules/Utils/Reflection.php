<?php

namespace Codeception\Module\Utils\Reflection;

function setObjectProperty($obj, $property, $value)
{
    $class = new \ReflectionClass(get_class($obj));
    $property = $class->getProperty($property);
    $property->setAccessible(true);
    $property->setValue($obj, $value);
}
