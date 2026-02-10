<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use Symfony\Contracts\Translation\TranslatorInterface;

class Trans extends AbstractTranslatable
{
    /**
     * Trans constructor.
     *
     * @param string $domain
     * @param string $locale
     */
    public function __construct($id, array $parameters = [], $domain = null, $locale = null)
    {
        $this->id = $id;
        $this->parameters = $parameters;
        $this->domain = $domain;
        $this->locale = $locale;
    }

    public function trans(TranslatorInterface $translator)
    {
        return $this->applyTransFormatters(
            $translator->trans(
                /** @Ignore */ $this->id,
                $this->transParams($this->parameters, $translator),
                $this->domain,
                $this->locale
            )
        );
    }

    protected function transParamsCallback(\Closure $callable)
    {
        return $callable(
            $this->id,
            $this->parameters,
            $this->domain,
            $this->locale
        );
    }
}
