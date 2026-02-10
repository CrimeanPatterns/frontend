<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Formatter\Tip;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Translation
{
    protected string $key;

    protected array $params;

    public function __construct(string $key, array $params = [])
    {
        $this->key = $key;
        $this->params = $params;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getParams(): array
    {
        $params = [];

        foreach (array_keys($this->params) as $k) {
            $value = $this->getParam($k);

            if (!is_null($value)) {
                $params[$k] = $value;
            }
        }

        return $params;
    }

    public function getParam(string $name)
    {
        if (isset($this->params[$name])) {
            $param = $this->params[$name];

            if (!is_scalar($param) && is_callable($param)) {
                return $param();
            }

            return $param;
        }

        return null;
    }
}
