<?php

namespace AwardWallet\Engine\azul\Email;

use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Parser\Util\PriceHelper;

class FlightReservation2025 extends \TAccountChecker
{
    public $mailFiles = "azul/it-907484152-pt.eml, azul/it-906866731-pt.eml, azul/it-918956662-pt.eml, azul/it-928243892-pt.eml";

    private $subjects = [
        'pt' => ['Reserva realizada com sucesso', 'faça o check-in do seu voo para']
    ];

    public $lang = '';

    public static $dictionary = [
        'pt' => [
            'Flight' => 'Voo',
            'confNumber' => [
                'Código de reserva',
                'código de reserva',
            ],
            'noConfNumber' => [
                'Após a confirmação do pagamento e do seu pedido, você receberá o código da sua reserva.',
                'Após a confirmação do pagamento e do seu pedido,você receberá o código da sua reserva.',
            ],
            'baggageValues' => ['peça(s)', 'kg'],
            'feeNames' => ['Taxas'],
        ]
    ];

    private $xpath = [
        'airportCode' => 'translate(normalize-space(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆∆")="∆∆∆"',
        'noDisplay' => 'ancestor-or-self::*[contains(translate(@style," ",""),"display:none")]',
    ];

    private $patterns = [
        'date' => '\b\d{1,2}\/\d{1,2}(?:\/\d{4})?\b', // 26/05/2025  |  26/05
        'time' => '\d{1,2}[:：]\d{2}(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM  |  2:00 p. m.
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[.@]voeazul-news\.com\.br$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array)$phrases as $phrase) {
                if (is_string($phrase) && array_key_exists('subject', $headers) && stripos($headers['subject'], $phrase) !== false)
                    return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $href = ['.voeazul-news.com.br/', 'click.voeazul-news.com.br'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query('//*[contains(normalize-space(),"Esse email é enviado por: AZUL LINHAS AÉREAS")]')->length === 0
        ) {
            return false;
        }
        return $this->findSegments()->length > 0;
    }

    private function findSegments(): \DOMNodeList
    {
        return $this->http->XPath->query("//*[ count(*[normalize-space()])<4 and *[1]/descendant::*[string-length(normalize-space())=3][1][{$this->xpath['airportCode']}] and *[3]/descendant::*[string-length(normalize-space())=3][1][{$this->xpath['airportCode']}] ]");
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if ( empty($this->lang) ) {
            $this->logger->debug("Can't determine a language!");
        }
        $email->setType('FlightReservation2025' . ucfirst($this->lang));

        $emailDate = strtotime($parser->getDate());

        if ($emailDate && $this->detectEmailFromProvider($parser->getCleanFrom()) === true) {
            $dateRelative = strtotime('-1 month', $emailDate);
        } else {
            $dateRelative = null;
        }

        $f = $email->add()->flight();

        $confirmation = $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))}]/following::text()[normalize-space()][1]", null, true, '/^[A-Z\d]{5,8}$/')
        ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('confNumber'))} and not({$this->xpath['noDisplay']})]/following::text()[normalize-space()][1]", null, true, '/^[A-Z\d]{5,8}$/');

        if ($confirmation) {
            $confirmationTitle = $this->http->FindSingleNode("//text()[{$this->starts($this->t('confNumber'))}]", null, true, "/^({$this->opt($this->t('confNumber'))})[\s:：]*$/u")
            ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('confNumber'))} and not({$this->xpath['noDisplay']})]", null, true, "/({$this->opt($this->t('confNumber'))})[\s:：]*$/u");
            $f->general()->confirmation($confirmation, $confirmationTitle);
        } elseif ($this->http->XPath->query("//*[{$this->contains($this->t('noConfNumber'))}]")->length > 0) {
            $f->general()->noConfirmation();
        }

        $segments = $this->findSegments();

        foreach ($segments as $root) {
            $s = $f->addSegment();

            $cabin = $this->http->FindSingleNode("preceding::text()[normalize-space()][position()<3][{$this->starts($this->t('Classe'))}]", $root, true, "/^{$this->opt($this->t('Classe'))}[-:\s]+([^\-:\s].*)$/");
            $s->extra()->cabin($cabin, false, true);

            /*
                HKG
                Hong Kong
                25/05/2025 - 19:40
            */
            $departureText = implode("\n", $this->http->FindNodes("*[1]/descendant::text()[normalize-space()]", $root));
            $arrivalText = implode("\n", $this->http->FindNodes("*[3]/descendant::text()[normalize-space()]", $root));

            if (preg_match("/^([A-Z]{3})(?:\n|$)/", $departureText, $m)) {
                $s->departure()->code($m[1]);
            }

            if (preg_match("/^([A-Z]{3})(?:\n|$)/", $arrivalText, $m)) {
                $s->arrival()->code($m[1]);
            }

            if (preg_match("/(?:^|\n)(?<date>{$this->patterns['date']})\s*[-–]+\s*(?<time>{$this->patterns['time']})$/", $departureText, $m)) {
                $dateDep = null;
                $dateDepNormal = $this->normalizeDate($m['date']);

                if (preg_match('/\b\d{4}$/', $m['date'])) {
                    $dateDep = strtotime($dateDepNormal);
                } elseif ($dateRelative) {
                    $dateDep = EmailDateHelper::parseDateRelative($dateDepNormal, $dateRelative, true, '%D%/%Y%');
                }

                $s->departure()->date(strtotime($m['time'], $dateDep));
            }

            if (preg_match("/(?:^|\n)(?<date>{$this->patterns['date']})\s*[-–]+\s*(?<time>{$this->patterns['time']})$/", $arrivalText, $m)) {
                $dateArr = null;
                $dateArrNormal = $this->normalizeDate($m['date']);

                if (preg_match('/\b\d{4}$/', $m['date'])) {
                    $dateArr = strtotime($dateArrNormal);
                } elseif ($dateRelative) {
                    $dateArr = EmailDateHelper::parseDateRelative($dateArrNormal, $dateRelative, true, '%D%/%Y%');
                }

                $s->arrival()->date(strtotime($m['time'], $dateArr));
            }

            $flightNumber = $this->http->FindSingleNode("*[2]/descendant::text()[{$this->eq($this->t('Flight'))}]/following::text()[normalize-space()][1]", $root, true, "/^\d+$/");
            $s->airline()->number($flightNumber)->noName();

            $operator = $this->http->FindSingleNode("following::text()[normalize-space()][1][{$this->eq($this->t('Operado por'))}]/ancestor::*[count(descendant::text()[normalize-space()])=2][1]", $root, true, "/^{$this->opt($this->t('Operado por'))}[-:\s]+([^\-:\s].*)$/");
            $s->airline()->operator($operator, false, true);
        }

        $travellers = $accounts = [];

        $passengerRows = $this->http->XPath->query("//tr[ count(*)=6 and *[2][normalize-space()] and *[6][{$this->eq($this->t('baggageValues'), "translate(.,'0123456789','')")}] ]");

        foreach ($passengerRows as $pRow) {
            $passengerName = $this->http->FindSingleNode("*[2]", $pRow);

            if (!in_array($passengerName, $travellers)) {
                $travellers[] = $passengerName;
            }

            $ffNumber = $this->http->FindSingleNode("*[4]", $pRow, true, "/^\d+$/");

            if ($ffNumber && !in_array($ffNumber, $accounts)) {
                $f->program()->account($ffNumber, false, $passengerName);
                $accounts[] = $ffNumber;
            }
        }

        if (count($travellers) === 0
            && preg_match("/(?:^|:\s*)({$this->patterns['travellerName']})\s*,\s*faça o check-in do seu voo para/iu", $parser->getSubject(), $m) // pt
        ) {
            $travellers[] = $m[1];
        }

        if (count($travellers) > 0) {
            $f->general()->travellers($travellers, true);
        }

        // price

        $totalPoints = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Total de pontos'))}] ]/*[normalize-space()][2]", null, true, '/^\d[,.’‘\'\d ]*$/');

        if ($totalPoints !== null) {
            $f->price()->spentAwards($totalPoints);
        }

        $totalPrice = $this->http->FindSingleNode("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('Total em dinheiro (tarifas + taxas)'))}] ]/*[normalize-space()][2]", null, true, "/^(.*?\d.*?)(?:\s+{$this->opt($this->t('em'))}\s+\d|$)/");

        if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $totalPrice, $matches)) {
            // it-906866731-pt.eml
            $f->price()->currency($matches['currency'])->total(PriceHelper::parse($matches['amount'], $matches['currency']));
        } else {
            // it-907484152-pt.eml
            $feeCurrencies = [];
            $feeRows = $this->http->XPath->query("//tr[ count(*[normalize-space()])=2 and *[normalize-space()][1][{$this->eq($this->t('feeNames'))}] ]");

            foreach ($feeRows as $feeRow) {
                $feeCharge = $this->http->FindSingleNode('*[normalize-space()][2]', $feeRow, true, '/^(.*?\d.*?)\s*(?:\(|$)/');

                if (preg_match('/^(?<currency>[^\-\d)(]+?)[ ]*(?<amount>\d[,.’‘\'\d ]*)$/u', $feeCharge, $m)) {
                    // R$ 1.803,92
                    $feeCurrencies[] = $m['currency'];
                    $feeName = $this->http->FindSingleNode('*[normalize-space()][1]', $feeRow, true, '/^(.+?)[\s:：]*$/u');
                    $f->price()->fee($feeName, PriceHelper::parse($m['amount'], $m['currency']));
                }
            }

            if (count(array_unique($feeCurrencies)) === 1) {
                $f->price()->currency($feeCurrencies[0]);
            }
        }

        return $email;
    }

    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function assignLang(): bool
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return false;
        }
        foreach (self::$dictionary as $lang => $phrases) {
            if ( !is_string($lang) || empty($phrases['Flight']) ) {
                continue;
            }
            if ($this->http->XPath->query("//text()[{$this->starts($phrases['Flight'])}]")->length > 0) {
                $this->lang = $lang;
                return true;
            }
        }
        return false;
    }

    private function t(string $phrase, string $lang = '')
    {
        if ( !isset(self::$dictionary, $this->lang) ) {
            return $phrase;
        }
        if ($lang === '') {
            $lang = $this->lang;
        }
        if ( empty(self::$dictionary[$lang][$phrase]) ) {
            return $phrase;
        }
        return self::$dictionary[$lang][$phrase];
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'normalize-space(' . $node . ')=' . $s;
        }, $field)) . ')';
    }

    private function starts($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return '';
        return '(?:' . implode('|', array_map(function($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    /**
     * @param string|null $text Unformatted string with date
     * @return string
     */
    private function normalizeDate(?string $text): string
    {
        if ( !is_string($text) || empty($text) )
            return '';
        $in = [
            // 26/05/2025 (Langs: pt)
            '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',
            // 26/05 (Langs: pt)
            '/^(\d{1,2})\/(\d{1,2})$/',
        ];
        $out = [
            '$2/$1/$3',
            '$2/$1',
        ];
        return preg_replace($in, $out, $text);
    }
}
