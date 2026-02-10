<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class HTMLPurifierTransformer implements DataTransformerInterface
{
    /**
     * @var \HTMLPurifier
     */
    private $purifier;

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        return $this->getPurifier()->purify($value);
    }

    /**
     * @return \HTMLPurifier
     */
    protected function getPurifier()
    {
        if (null === $this->purifier) {
            /** @var \HTMLPurifier_Config $config */
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('CSS.Trusted', true);
            $config->set('HTML.MaxImgLength', null);
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(.*\.youcanbook.me\/)%');
            $config->set('Attr.DefaultImageAlt', 'img');
            $config->set('Cache.DefinitionImpl', null);
            $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
            $this->purifier = new \HTMLPurifier($config);
        }

        return $this->purifier;
    }
}
