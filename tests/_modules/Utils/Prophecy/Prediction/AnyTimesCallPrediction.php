<?php

namespace AwardWallet\Tests\Modules\Utils\Prophecy\Prediction;

use Prophecy\Prediction\PredictionInterface;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;

class AnyTimesCallPrediction implements PredictionInterface
{
    public function check(array $calls, ObjectProphecy $object, MethodProphecy $method)
    {
    }
}
