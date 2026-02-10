<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

/**
 * Transforms base features into polynomial features to capture non-linear relationships.
 * This allows using linear regression models to learn non-linear patterns.
 */
class PolynomialFeatureTransformer
{
    /**
     * Maximum polynomial degree to generate.
     */
    private int $degree;

    /**
     * Whether to include interaction terms between different features.
     */
    private bool $includeInteractions;

    /**
     * Constructor.
     *
     * @param int $degree Maximum polynomial degree (default: 2)
     * @param bool $includeInteractions Whether to include interaction terms (default: true)
     */
    public function __construct(int $degree = 2, bool $includeInteractions = true)
    {
        $this->degree = max(1, $degree); // Ensure degree is at least 1
        $this->includeInteractions = $includeInteractions;
    }

    /**
     * Transform base features into polynomial features.
     *
     * @param array $features Base features (key => value pairs)
     * @return array Transformed features including polynomial terms
     */
    public function transform(array $features): array
    {
        $transformedFeatures = [];

        // Keep original features
        foreach ($features as $key => $value) {
            $transformedFeatures["original_{$key}"] = $value;
        }

        // Add polynomial features up to the specified degree
        for ($d = 2; $d <= $this->degree; $d++) {
            foreach ($features as $key => $value) {
                $transformedFeatures["pow{$d}_{$key}"] = pow($value, $d);
            }
        }

        // Add interaction terms if enabled
        if ($this->includeInteractions) {
            $keys = array_keys($features);
            $count = count($keys);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $key1 = $keys[$i];
                    $key2 = $keys[$j];
                    $transformedFeatures["interaction_{$key1}_{$key2}"] = $features[$key1] * $features[$key2];
                }
            }
        }

        return $transformedFeatures;
    }

    /**
     * Get the maximum polynomial degree.
     */
    public function getDegree(): int
    {
        return $this->degree;
    }

    /**
     * Check if interaction terms are included.
     */
    public function getIncludeInteractions(): bool
    {
        return $this->includeInteractions;
    }
}
