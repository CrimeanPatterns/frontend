<?php

namespace AwardWallet\MainBundle\Globals\GoogleVision;

class GoogleVisionResponseConverter
{
    public function convert(array $googleResponse): GoogleVisionResult
    {
        $result = new GoogleVisionResult();

        if (isset($googleResponse['logoAnnotations'])) {
            foreach ($googleResponse['logoAnnotations'] as $logoData) {
                if (
                    isset($logoData['description'], $logoData['score'])
                    && !$this->isKnownLogoDiscrepancies($logoData)
                ) {
                    $result->logos[] = new GoogleVisionLogo($logoData['description'], $logoData['score']);
                }
            }
        }

        if (isset($googleResponse['textAnnotations'][0]['description'])) {
            $result->text = $googleResponse['textAnnotations'][0]['description'];
        }

        return $result;
    }

    protected function isKnownLogoDiscrepancies(array $LogoData): bool
    {
        if ($this->isTAMAirlines($LogoData)) {
            return true;
        }

        // add others here...

        return false;
    }

    /**
     * Star alliance logos treated as TAM Airlines logos.
     */
    protected function isTAMAirlines(array $logoData): bool
    {
        if (
            isset($logoData['mid'])
            && in_array($logoData['mid'], ['/g/11bwypprlw', '/m/014vt3'], true)
            && ($logoData['score'] < 0.5)
            && (stripos($logoData['description'], 'tam') !== false)
        ) {
            return true;
        }

        return false;
    }
}
