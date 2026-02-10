<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

/**
 * Forked tranlator to debug translation keys.
 */
class Translator extends \Symfony\Bundle\FrameworkBundle\Translation\Translator
{
    /**
     * @var bool
     */
    private $dumpKeysEnabled = false;
    private $enableDesktopHelper = false;

    /**
     * @var array
     */
    private $dumpedKeys = [];

    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $translation = parent::trans($id, $parameters, $domain, $locale);

        if ($this->dumpKeysEnabled && (0 !== strcmp($id, $translation))) {
            $dumpedKey = $this->formatDumpedKey($translation, $id, $parameters, $domain);
            $this->dumpedKeys[$translation] = $dumpedKey;
        }

        if ($this->isEnableDesktopHelper()) {
            if (!$domain) {
                $domain = 'messages';
            }
            $data = [
                'domain' => $domain,
                'id' => $id,
                'message' => $translation,
            ];
            $encoded = base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES));

            return "<mark data-title=\"$encoded\">" . $translation . "</mark>";
        } else {
            return $translation;
        }
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $translation = parent::trans($id, array_merge($parameters, ['%count%' => $number]), $domain, $locale);

        if ($this->dumpKeysEnabled && (0 !== strcmp($id, $translation))) {
            $dumpedKey = $this->formatDumpedKey($translation, $id, $parameters, $domain);
            $dumpedKey['number'] = $number;
            $this->dumpedKeys[$translation] = $dumpedKey;
        }

        if ($this->isEnableDesktopHelper()) {
            if (!$domain) {
                $domain = 'messages';
            }
            $data = [
                'domain' => $domain,
                'id' => $id,
                'message' => $translation,
            ];
            $encoded = base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES));

            return "<mark data-title=\"$encoded\">" . $translation . "</mark>";
        } else {
            return $translation;
        }
    }

    /**
     * @param bool $dumpKeysEnabled
     * @return Translator
     */
    public function setDumpKeysEnabled($dumpKeysEnabled = true)
    {
        $this->dumpKeysEnabled = $dumpKeysEnabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getDumpedKeys()
    {
        return $this->dumpedKeys;
    }

    public function setDumpedKeys(array $dumpedKeys)
    {
        $this->dumpedKeys = $dumpedKeys;
    }

    /**
     * @return bool
     */
    public function isDumpKeysEnabled()
    {
        return $this->dumpKeysEnabled;
    }

    /**
     * @return bool
     */
    public function isEnableDesktopHelper()
    {
        return $this->enableDesktopHelper;
    }

    /**
     * @param bool $enableDesktopHelper
     */
    public function setEnableDesktopHelper($enableDesktopHelper)
    {
        $this->enableDesktopHelper = $enableDesktopHelper;
    }

    protected function formatDumpedKey($translation, $id, array $parameters, $domain)
    {
        $dumpedKey = [
            'key' => $id,
            'domain' => (null === $domain ? 'messages' : $domain),
            'value' => $translation,
        ];

        if (count($parameters) !== 0) {
            $dumpedKey['parameters'] = $parameters;
        }

        return $dumpedKey;
    }
}
