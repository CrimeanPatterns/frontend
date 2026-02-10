<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class HtmlSpecialCharsTransformer implements DataTransformerInterface
{
    /**
     * @param string $escapedString
     * @return string
     * @throws TransformationFailedException
     */
    public function transform($escapedString)
    {
        if (null === $escapedString) {
            return '';
        }

        if (!is_string($escapedString)) {
            throw new TransformationFailedException("Expected string, got " . gettype($escapedString));
        }

        return htmlspecialchars_decode($escapedString);
    }

    /**
     * @param string $string
     * @return string
     * @throws TransformationFailedException
     */
    public function reverseTransform($string)
    {
        if (null === $string) {
            return '';
        }

        if (!is_string($string)) {
            throw new TransformationFailedException("Expected string, got " . gettype($string));
        }

        return htmlspecialchars($string);
    }
}
