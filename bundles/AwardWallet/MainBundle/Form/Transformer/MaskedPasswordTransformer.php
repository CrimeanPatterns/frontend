<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class MaskedPasswordTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $originalPassword;

    public function transform($value)
    {
        $this->originalPassword = $value;
        $random = $this->generatePassCode(strlen($value));

        return $value = ['password' => $random, 'unmodified' => $random];
    }

    public function reverseTransform($value)
    {
        if (!empty($value['password']) && $value['password'] == $value['unmodified']) {
            return $this->originalPassword;
        } else {
            return $value['password'];
        }
    }

    private function generatePassCode($length)
    {
        $chars = ['❶', '❷', '❸', '❹', '❺', '❻', '❼', '❽', '❾'];
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[array_rand($chars)];
        }

        return $result;
    }
}
