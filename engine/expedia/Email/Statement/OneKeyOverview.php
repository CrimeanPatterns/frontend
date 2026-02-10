<?php

namespace AwardWallet\Engine\expedia\Email\Statement;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class OneKeyOverview extends \TAccountChecker
{
    public $mailFiles = "expedia/statements/it-903012704.eml, expedia/statements/it-903091082.eml";

    public $lang = 'en';
    public $providerCode;

    public $detectSubject = [
        'One Key overview',
    ];

    public static $detectProviders = [
        'hotels' => [
            'from'    => ['mail@eg.hotels.com'],
            'bodyUrl' => '.hotels.com',
        ],
        'expedia' => [
            'from'    => ['mail@eg.expedia.com', 'mail_at_eg_expedia_com'],
            'bodyUrl' => '.expedia.com',
        ],
        'homeaway' => [
            'from'    => ['mail@eg.vrbo.com'],
            'bodyUrl' => '.vrbo.com',
        ],
    ];
    public static $dictionary = [
        "en" => [
            'Your One Key account monthly overview'     => ["Your One Key account monthly overview"],
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]expedia\.com$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        $detectedProvider = false;

        foreach (self::$detectProviders as $code => $detects) {
            if (!empty($detects['from'])) {
                foreach ($detects['from'] as $dFrom) {
                    if (strpos($headers['from'], $dFrom) !== false) {
                        $this->providerCode = $code;
                        $detectedProvider = true;

                        break;
                    }
                }
            }
        }

        if ($detectedProvider === true) {
            foreach ($this->detectSubject as $subject) {
                if (strpos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->detectEmailByHeaders($parser->getHeaders()) !== true) {
            return false;
        }

        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Your One Key account monthly overview"])
                && $this->http->XPath->query("//*[{$this->starts($dict['Your One Key account monthly overview'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        if ($this->http->XPath->query("//a[contains(@href,'https://www.boxbe.com/')]")->length > 0) {
            // many letters with information in html-attachments (www.boxbe.com)
            $htmls = implode("\n", $this->getHtmlAttachments($parser));
            $this->http->SetEmailBody($htmls);
        }

        $this->assignProvider($parser->getHeaders());
        $email->setProviderCode($this->providerCode);

        $st = $email->add()->statement();

        $balance = $this->http->FindSingleNode("//text()[{$this->eq($this->t('in OneKeyCash'))}]/preceding::text()[normalize-space()][1]",
            null, true, "/^\D*(\d.*?)\D*$/");

        $st->setBalance(PriceHelper::parse($balance));

        $name = $this->http->FindSingleNode("//text()[{$this->contains($this->t(", here's a snapshot of your OneKeyCash"))}]",
            null, true, "/^\s*(\D+?)\s*{$this->opt($this->t(", here's a snapshot of your OneKeyCash"))}/");

        if (!empty($name)) {
            $st->addProperty('Name', $name);
        }

        $status = $this->http->FindSingleNode("//text()[{$this->eq($this->t('in OneKeyCash'))}]/preceding::text()[normalize-space() and string-length(normalize-space()) > 1][2]",
            null, true, "/^\s*(Blue|Silver|Gold|Platinum)\s*$/");

        switch ($this->providerCode) {
            case 'hotels':
            case 'expedia':
                $st->addProperty('Status', $status);

            break;

            case 'homeaway':
                $st->addProperty('Tier', $status);

            break;
        }

        $tripToNextStatus = $this->http->FindSingleNode("//text()[{$this->contains($this->t('trip elements collected'))}]",
            null, true, "/^\s*(\d+)\s+{$this->opt($this->t('trip elements collected'))}/");

        if ($tripToNextStatus === null) {
            $tripToNextStatus = $this->http->FindSingleNode("//text()[{$this->starts($this->t('of'))}][{$this->contains($this->t('trip elements collected'))}]" .
                "/preceding::text()[normalize-space()][1]",
                null, true, "/^\s*(\d+)\s*$/");
        }

        switch ($this->providerCode) {
            case 'hotels':
                $st->addProperty('TripToNextStatus', $tripToNextStatus);

                break;

            case 'expedia':
                $st->addProperty('TripsToTheNextTier', $tripToNextStatus);

                break;
        }

        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProviders);
    }

    private function assignLang(): bool
    {
        foreach (self::$dictionary as $lang => $dict) {
            if (!empty($dict["Your latest One Key™ update"])
                && $this->http->XPath->query("//*[{$this->eq($dict['Your latest One Key™ update'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    private function assignProvider($headers): bool
    {
        foreach (self::$detectProviders as $providerCode => $detects) {
            if (!empty($detects['from'])) {
                foreach ($detects['from'] as $dFrom) {
                    if (strpos($headers['from'], $dFrom) !== false) {
                        $this->providerCode = $providerCode;

                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function contains($field, $text = 'normalize-space(.)')
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($text) {
            return 'contains(' . $text . ',"' . $s . '")';
        }, $field)) . ')';
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'normalize-space(.)="' . $s . '"';
        }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) {
            return 'starts-with(normalize-space(.),"' . $s . '")';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function getHtmlAttachments(PlancakeEmailParser $parser, $length = 6000): array
    {
        $result = [];
        $altCount = $parser->countAlternatives();

        for ($i = 0; $i < $parser->countAttachments() + $altCount; $i++) {
            $html = $parser->getAttachmentBody($i);
            $info = $parser->getAttachmentHeader($i, 'content-type');

            if (preg_match("#^text/html;#", $info) && is_string($html) && strlen($html) > $length) {
                $result[] = $html;
            }
        }

        return $result;
    }
}
