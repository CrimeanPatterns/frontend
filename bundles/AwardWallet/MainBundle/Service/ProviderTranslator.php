<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Provider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProviderTranslator
{
    private const TRANSLATION_DOMAIN = 'provider';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function translateDisplayNameByEntity(Provider $provider, ?string $locale = null): ?string
    {
        if ($locale === 'en' || (empty($locale) && $this->translator->getLocale() === 'en')) {
            return $provider->getDisplayname();
        }

        $transId = 'displayname.' . $provider->getId();
        $catalogue = $this->translator->getCatalogue($locale);

        if ($catalogue->has($transId, self::TRANSLATION_DOMAIN)) {
            return $this->translator->trans($transId, [], self::TRANSLATION_DOMAIN, $locale);
        }

        return $provider->getDisplayname();
    }

    public function translateDisplayNameByScalars(int $providerId, ?string $displayName, ?string $locale = null): ?string
    {
        if ($locale === 'en' || (is_null($locale) && $this->translator->getLocale() === 'en')) {
            return $displayName;
        }

        $transId = 'displayname.' . $providerId;
        $catalogue = $this->translator->getCatalogue($locale);

        if ($catalogue->has($transId, self::TRANSLATION_DOMAIN)) {
            return $this->translator->trans($transId, [], self::TRANSLATION_DOMAIN, $locale);
        }

        return $displayName;
    }
}
