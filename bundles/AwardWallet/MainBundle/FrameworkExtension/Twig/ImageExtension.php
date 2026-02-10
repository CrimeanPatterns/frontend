<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ImageExtension extends AbstractExtension
{
    private Packages $packages;

    private bool $isDev;

    public function __construct(Packages $packages, string $env)
    {
        $this->packages = $packages;
        $this->isDev = in_array($env, ['dev', 'staging']);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('image_src', [$this, 'imageSrc'], ['is_safe' => ['html']]),
            new TwigFunction('image_srcset', [$this, 'imageSrcset'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * generates the src and srcset attributes for an image.
     */
    public function imageSrc(string $imagePath, string $basePath = '/blocks/', bool $onlySrcset = false): string
    {
        $imagePath = ($this->isDev ? 'a' : 'p') . $basePath . $imagePath;

        if (strpos($imagePath, '@{') !== false) {
            // image path contains a dimension placeholder
            if (preg_match('/(?<=@){([\d\-,\s]+)}(x|w)/', $imagePath, $matches)) {
                // extract the dimensions and units
                $search = $matches[0];
                $dimensions = $matches[1];
                $units = $matches[2];
                // convert the dimensions to an array
                $range = it(explode(',', $dimensions))
                    ->map(function ($dimension) {
                        $dimension = trim($dimension);

                        // check if the dimension is a single value
                        if (is_numeric($dimension)) {
                            return (int) $dimension;
                        }

                        // check if the dimension is a range
                        if (strpos($dimension, '-') !== false) {
                            $range = array_map('trim', explode('-', $dimension));

                            if (\count($range) !== 2 || !is_numeric($range[0]) || !is_numeric($range[1])) {
                                throw new \InvalidArgumentException('Invalid range: ' . $dimension);
                            }

                            $start = (int) $range[0];
                            $end = (int) $range[1];

                            return range($start, $end);
                        }

                        return null;
                    })
                    ->filterNotNull()
                    ->flatten()
                    ->toArray();

                if (\count($range) === 0) {
                    throw new \InvalidArgumentException('No dimensions found in image path');
                }

                $src = $this->packages->getUrl(str_replace($search, $range[0] . $units, $imagePath));

                // sort the dimensions and remove duplicates
                sort($range);
                $range = array_unique($range);
                $srcset = [];

                foreach ($range as $dimension) {
                    // add the dimension to the srcset
                    $srcset[] = sprintf(
                        '%s %d%s',
                        $this->packages->getUrl(str_replace($search, $dimension . $units, $imagePath)),
                        $dimension,
                        $units
                    );
                }

                if ($onlySrcset) {
                    return $this->formatAttributes(['srcset' => implode(', ', $srcset)]);
                }

                return $this->formatAttributes([
                    'src' => $src,
                    'srcset' => implode(', ', $srcset),
                ]);
            }
        }

        if ($onlySrcset) {
            return $this->formatAttributes(['srcset' => $this->packages->getUrl($imagePath)]);
        }

        return $this->formatAttributes(['src' => $this->packages->getUrl($imagePath)]);
    }

    public function imageSrcset(string $imagePath, string $basePath = '/blocks/'): string
    {
        return $this->imageSrc($imagePath, $basePath, true);
    }

    private function formatAttributes(array $attrs): string
    {
        $formatted = [];

        foreach ($attrs as $name => $value) {
            $formatted[] = sprintf(
                '%s="%s"',
                $name,
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false)
            );
        }

        return implode(' ', $formatted);
    }
}
