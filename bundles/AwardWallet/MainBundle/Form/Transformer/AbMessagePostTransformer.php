<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class AbMessagePostTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if (is_null($value)) {
            return "";
        }

        return htmlspecialchars_decode(preg_replace('/\<br(\s*)?\/?\>/i', "", $value));
    }

    public function reverseTransform($value)
    {
        return nl2br(htmlspecialchars($value));
    }
}
