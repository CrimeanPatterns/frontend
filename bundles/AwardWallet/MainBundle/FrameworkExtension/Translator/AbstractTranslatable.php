<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTranslatable implements TranslatableInterface
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var array
     */
    protected $parameters;
    /**
     * @var string
     */
    protected $domain;
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var callable[]
     */
    protected $transFormatters = [];

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function addTransFormatter(callable $callable)
    {
        $this->transFormatters[] = $callable;

        return $this;
    }

    protected function applyTransFormatters($a)
    {
        foreach ($this->transFormatters as $callable) {
            $a = call_user_func($callable, $a);
        }

        return $a;
    }

    protected function transParams(array $parameters, TranslatorInterface $translator)
    {
        foreach ($parameters as $key => $value) {
            if ($value instanceof TranslatableInterface) {
                if ($value instanceof AbstractTranslatable) {
                    if (null === $value->getLocale()) {
                        $value->setLocale($this->locale);
                    }
                }

                $parameters[$key] = $value->trans(/** @Ignore */ $translator);
            } elseif (
                is_object($value)
                && ($value instanceof \Closure)
            ) {
                $parameters[$key] = $this->transParamsCallback($value);
            }
        }

        return $parameters;
    }

    abstract protected function transParamsCallback(\Closure $callable);
}
