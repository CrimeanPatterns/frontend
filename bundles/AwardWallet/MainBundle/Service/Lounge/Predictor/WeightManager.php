<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

use Symfony\Component\Yaml\Yaml;

class WeightManager
{
    public function saveWeights(array $weights): bool
    {
        try {
            $yamlContent = Yaml::dump($weights);
            file_put_contents(__DIR__ . '/weights.yml', $yamlContent);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getWeights(): ?array
    {
        try {
            $yamlContent = file_get_contents(__DIR__ . '/weights.yml');
            $weights = Yaml::parse($yamlContent);

            return is_array($weights) ? $weights : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
