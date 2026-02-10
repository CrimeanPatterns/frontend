<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class Predictor
{
    /**
     * @var iterable<ComparatorInterface>
     */
    private iterable $loungePredictorComparators;

    private Normalizer $normalizer;

    private WeightManager $weightManager;

    private ?array $cachedWeights = null;

    private PolynomialFeatureTransformer $transformer;

    public function __construct(
        iterable $loungePredictorComparators,
        Normalizer $normalizer,
        WeightManager $weightManager
    ) {
        $this->loungePredictorComparators = $loungePredictorComparators;
        $this->normalizer = $normalizer;
        $this->weightManager = $weightManager;
        $this->transformer = new PolynomialFeatureTransformer(2, true);
    }

    public function predict(LoungeInterface $lounge1, LoungeInterface $lounge2, ?array $weights = null): float
    {
        if (is_null($weights)) {
            $weights = $this->getWeights();
        }

        $lounge1Normalized = $this->normalizer->normalize($lounge1);
        $lounge2Normalized = $this->normalizer->normalize($lounge2);

        // Get base features from comparators
        $baseFeatures = [];

        foreach ($this->loungePredictorComparators as $comparator) {
            $featureId = get_class($comparator);
            $featureValue = $comparator->compare($lounge1Normalized, $lounge2Normalized);
            $baseFeatures[$featureId] = $this->sanitizeFeatureValue($featureValue);
        }

        // Apply polynomial transformation to capture non-linear relationships
        $transformedFeatures = $this->transformer->transform($baseFeatures);

        // Calculate similarity with polynomial features
        $similarity = 0;
        $totalAbsWeight = 0;

        foreach ($transformedFeatures as $featureId => $value) {
            // Use default weight of 0 for transformed features that don't have weights yet
            $weight = $weights[$featureId] ?? 0;
            $absWeight = abs($weight);
            $totalAbsWeight += $absWeight;
            $similarity += $value * $weight;
        }

        if ($totalAbsWeight === 0) {
            return 0.5;
        }

        return max(0, min(1, $similarity / $totalAbsWeight));
    }

    public function clearCache(): void
    {
        $this->cachedWeights = null;
    }

    /**
     * Sanitize feature value to ensure it's between 0 and 1.
     *
     * @param float $value The feature value to sanitize
     * @return float Sanitized value between 0 and 1
     */
    private function sanitizeFeatureValue(float $value): float
    {
        return max(0, min(1, $value));
    }

    private function getWeights(): array
    {
        if (!is_null($this->cachedWeights)) {
            return $this->cachedWeights;
        }

        $this->cachedWeights = $this->weightManager->getWeights();

        if (is_null($this->cachedWeights)) {
            throw new \RuntimeException('Weights are not initialized');
        }

        return $this->cachedWeights;
    }
}
