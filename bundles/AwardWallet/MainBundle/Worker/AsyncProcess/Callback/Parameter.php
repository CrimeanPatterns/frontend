<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess\Callback;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"ALL"})
 */
final class Parameter implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param array{value: string}|string $name
     */
    public function __construct($name)
    {
        if (\is_array($name)) {
            $name = $name['value'];
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function jsonSerialize()
    {
        return [
            'class' => 'Parameter',
            'name' => $this->name,
        ];
    }
}
