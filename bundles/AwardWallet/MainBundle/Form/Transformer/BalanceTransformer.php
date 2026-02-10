<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BalanceTransformer implements DataTransformerInterface
{
    public $allowEmpty = false;

    public function __construct($allowEmpty = false)
    {
        $this->allowEmpty = $allowEmpty;
    }

    public function transform($value)
    {
        if (intval($value) < floatval($value)) {
            return $value;
        } else {
            if ($this->allowEmpty && intval($value) == 0) {
                return '';
            }

            return intval($value);
        }
    }

    public function reverseTransform($value)
    {
        $value = trim($value);

        if (empty($value)) {
            return $value;
        }
        // @TODO: get constants from current user settings
        $thousands_sep = ' ';
        $dec_point = '.';
        $value = preg_replace("/[^\dk\\{$thousands_sep}\\{$dec_point}\+]/ims", "", strval($value));
        $value = trim($value);
        $value = preg_replace("/\\{$thousands_sep}+/ims", "", $value);
        $value = preg_replace("/\\{$dec_point}/ims", ".", $value);

        if (preg_match("/^(\d+)k$/ims", $value, $matches)) {
            $value = $matches[1] . '000';
        }
        $value = trim(preg_replace("/k/ims", "", $value));

        if (empty($value)) {
            throw new TransformationFailedException("balance.invalid");
        }

        return $value;
    }
}
