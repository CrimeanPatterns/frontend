<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Resolver;

use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslationCacheTrait
{
    /** @var TranslatorInterface */
    protected $translator;
    protected $messages = [];

    protected function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (!isset($domain)) {
            $domain = 'messages';
        }

        if (!isset($locale)) {
            $locale = $this->translator->getLocale();
        }

        if (isset($this->messages[$locale][$domain][$id])) {
            return $this->messages[$locale][$domain][$id];
        }
        $result = $this->translator->trans(/** @Ignore */ $id, $parameters, $domain, $locale);

        if (!sizeof($parameters)) {
            $this->messages[$locale][$domain][$id] = $result;
        }

        return $result;
    }

    protected function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans(/** @Ignore */ $id, array_merge($parameters, ['%count%' => $number]), $domain, $locale);
    }
}
