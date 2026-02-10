<?php

namespace AwardWallet\MainBundle\Timeline\Formatter;

use AwardWallet\MainBundle\Timeline\QueryOptions;

class CallableFormatHandler implements FormatHandlerInterface
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function handle(array $items, QueryOptions $options): array
    {
        return ($this->callable)($items, $options);
    }

    public function addItemFormatter(string $type, ItemFormatterInterface $formatter)
    {
        throw new \LogicException('should not be called');
    }
}
