<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use Symfony\Contracts\Translation\TranslatorInterface;

class TransChoice extends Trans
{
    /**
     * @var int
     */
    protected $number;

    /**
     * TransChoice constructor.
     *
     * @param int $number
     * @param string $domain
     * @param string $locale
     */
    public function __construct($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        parent::__construct($id, $parameters, $domain, $locale);

        $this->number = $number;
    }

    public function trans(TranslatorInterface $translator)
    {
        return $this->applyTransFormatters(
            $translator->trans(
                /** @Ignore */ $this->id,
                $this->transParams(array_merge($this->parameters, ['%count%' => $this->number]), $translator),
                $this->domain,
                $this->locale
            )
        );
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    protected function transParamsCallback(\Closure $callable)
    {
        return $callable(
            $this->id,
            $this->number,
            $this->parameters,
            $this->domain,
            $this->locale
        );
    }
}
