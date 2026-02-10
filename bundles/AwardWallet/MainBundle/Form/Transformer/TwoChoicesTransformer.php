<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TwoChoicesTransformer implements DataTransformerInterface
{
    private $yesValue;
    private $noValue;

    public function __construct($yesValue, $noValue)
    {
        $this->yesValue = $yesValue;
        $this->noValue = $noValue;
    }

    public function transform($value)
    {
        if (empty($value) && '0' != $value) {
            return ['choice' => null, 'text' => ""];
        }

        if ($value == $this->yesValue) {
            return ['choice' => $this->yesValue, 'text' => ""];
        }

        return ['choice' => $this->noValue, 'text' => $value];
    }

    public function reverseTransform($value)
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (!isset($value['choice'])) {
            return null;
        }

        if (strtolower($value['choice']) == strtolower($this->yesValue)) {
            return $this->yesValue;
        }

        if (!isset($value['text'])) {
            return null;
        }
        $value['text'] = trim($value['text']);

        if (empty($value['text'])) {
            return null;
        }

        return $value['text'];
    }
}
