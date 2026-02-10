<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use Symfony\Contracts\Translation\TranslatorInterface;

class Util
{
    private static $propertyName = [];

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function translatePropertyName(string $name, string $type, ?string $lang = null): array
    {
        if (!isset($lang)) {
            $lang = $this->translator->getLocale();
        }
        $key = PropertiesList::getTranslationKeyForProperty($name, $type);
        $translation = $this->translate($key, $lang);

        if ($translation !== $key) {
            return [
                'translation' => $translation,
                'key' => $key,
            ];
        } else {
            return [
                'translation' => $name,
            ];
        }
    }

    /**
     * @param Itinerary|Tripsegment $it
     * @return string
     */
    public static function getKind($it)
    {
        if ($it instanceof Tripsegment) {
            $class = get_class($it->getTripid());
        } else {
            $class = get_class($it);
        }
        $parts = explode("\\", $class);

        return $parts[sizeof($parts) - 1];
    }

    private function translate(string $key, string $locale): string
    {
        if (isset(self::$propertyName[$locale][$key])) {
            return self::$propertyName[$locale][$key];
        }

        return self::$propertyName[$locale][$key] = $this->translator->trans(/** @Ignore */ $key, [], 'trips', $locale);
    }
}
