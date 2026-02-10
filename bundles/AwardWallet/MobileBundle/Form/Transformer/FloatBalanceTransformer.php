<?php

namespace AwardWallet\MobileBundle\Form\Transformer;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\Form\DataTransformerInterface;

class FloatBalanceTransformer implements DataTransformerInterface
{
    /**
     * @var LocalizeService
     */
    private $localizeService;

    public function __construct(LocalizeService $localizeService)
    {
        $this->localizeService = $localizeService;
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        $value = $this->translateBalance($value, $this->localizeService->getThousandsSeparator(), $this->localizeService->getDecimalPoint());

        return $value;
    }

    protected function translateBalance($balance, $thousands_sep, $dec_point = '.')
    {
        $balance = preg_replace("/[^\dk\\{$thousands_sep}\\{$dec_point}\+]/ims", "", strval($balance));
        $balance = trim($balance);
        $balance = preg_replace("/\\{$thousands_sep}+/ims", "", $balance);
        $balance = preg_replace("/\\{$dec_point}/ims", ".", $balance);

        if (preg_match("/^(\d+)k$/ims", $balance, $matches)) {
            $balance = $matches[1] . '000';
        }
        $balance = preg_replace("/k/ims", "", $balance);
        $balance = floatval($balance);

        return $balance;
    }
}
