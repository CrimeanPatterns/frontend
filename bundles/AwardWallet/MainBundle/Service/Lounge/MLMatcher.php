<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\Predictor\Predictor;
use AwardWallet\MainBundle\Service\Lounge\Predictor\WeightManager;

class MLMatcher implements MatcherInterface
{
    public const NAME = 'ml';

    private Predictor $predictor;

    private WeightManager $weightManager;

    public function __construct(Predictor $predictor, WeightManager $weightManager)
    {
        $this->predictor = $predictor;
        $this->weightManager = $weightManager;
    }

    public function getSimilarity(LoungeInterface $lounge1, LoungeInterface $lounge2): float
    {
        return $this->predictor->predict($lounge1, $lounge2, $this->weightManager->getWeights());
    }

    public static function getThreshold(): float
    {
        return 0.7;
    }

    public static function getName(): string
    {
        return static::NAME;
    }
}
