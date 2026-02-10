<?php

namespace AwardWallet\Engine\azul\Email;

use AwardWallet\Schema\Parser\Email\Email;

class AlteradoVoo extends \TAccountChecker
{
    public $mailFiles = "azul/it-720760262-pt.eml, azul/it-928576490-pt.eml";

    public $lang = 'pt';
    public static $dictionary = [
        'pt' => [
            'hello' => ['Ops! Algo precisou ser ajustado em sua viagem,', 'Ola,', 'Olá,'],
            'New Flight' => ['Novo Voo'],
            'statusValues' => ['CONFIRMADO', 'ALTERADO'],
            'statusForSkip' => ['ALTERADO'],
        ],
    ];

    private $detectSubject = [
        // pt
        'foi alterado com sucesso', // Seu voo para Vitória foi alterado com sucesso
        'Temos uma informação importante sobre o seu voo para',
    ];
    private $detectBody = [
        'pt' => [
            'foi alterado com sucesso.', 'sofreu alterações.',
        ],
    ];

    // Main Detects Methods
    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.](?:news-voeazul|voeazul-news)\.com\b.*$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        foreach ($this->detectSubject as $dSubject) {
            if (array_key_exists('subject', $headers) && stripos($headers['subject'], $dSubject) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $href = ['.news-voeazul.com.br/', '.voeazul-news.com.br/'];

        if ($this->http->XPath->query("//a[{$this->contains($href, '@href')} or {$this->contains($href, '@originalsrc')}]")->length === 0
            && $this->http->XPath->query("//*[{$this->eq(['azul@news-voeazul.com.br', 'noreply@voeazul-news.com.br'])}]")->length === 0
        ) {
            return false;
        }

        foreach ($this->detectBody as $detectBody) {
            if ($this->http->XPath->query("//*[{$this->contains($detectBody)}]")->length > 0) {
                return true;
            }
        }

        return false;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        $this->parseEmailHtml($email);

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

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

    private function parseEmailHtml(Email $email): void
    {
        $f = $email->add()->flight();

        // General
        $f->general()
            ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('Código da Reserva:'))}]/following::text()[normalize-space()][1]",
                null, true, "/^\s*([A-Z\d]{5,7})\s*$/"))
            ->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('hello'))}]",
                null, true, "/^\s*{$this->opt($this->t('hello'))}\s*([[:alpha:]][[:alpha:] \-]+)\s*\.\s*$/u"), false)
        ;

        // Segments
        $xpath = "//img[contains(@src, 'icon_aviao_cinza.png')]/ancestor::tr[count(*) = 3][1]";
        $nodes = $this->http->XPath->query($xpath);

        if ($nodes->length === 0) {
            $xpath = "//text()[starts-with(normalize-space(), 'Voo ')][contains(translate(.,'1234567890','dddddddddd'),'dddd')]/ancestor::tr[count(*) = 3][1]";
            $nodes = $this->http->XPath->query($xpath);
        }

        foreach ($nodes as $root) {
            $status = $this->http->FindSingleNode("preceding::text()[contains(.,'/')][1]/ancestor::tr[ preceding-sibling::tr[normalize-space()] ][1][count(preceding-sibling::tr[normalize-space()])=1]/preceding-sibling::tr[normalize-space()]", $root, true, "/^\s*({$this->opt($this->t('statusValues'))})\s*$/i")
                ?? $this->http->FindSingleNode("preceding::text()[contains(.,'/')][1]/ancestor::tr[ descendant::text()[normalize-space()][2] ][1]", $root, true, "/^\s*({$this->opt($this->t('New Flight'))})\s*[A-Z]{3}/i") // it-928576490-pt.eml
            ;

            if (!$status) {
                $this->logger->debug('Unknown segment detected!');
                $f->addSegment(); // for 100% fail
            }

            if ($status && preg_match("/^{$this->opt($this->t('statusForSkip'))}$/i", $status)) {
                $this->logger->debug('Segment is "' . $status . '"! Skip.');

                continue;
            }

            $s = $f->addSegment();
            $s->extra()->status($status, false, true);

            // Airline
            $s->airline()
                ->name('AD')
                ->number($this->http->FindSingleNode("*[2]", $root, true, "/^\s*Voo\s*(\d{1,4})\s*$/"));

            $date = $this->normalizeDate($this->http->FindSingleNode("preceding::text()[contains(., '/')][1]", $root, true, "/^\s*(.*\d{4}.*)\s*$/"));

            // Departure
            $time = $this->http->FindSingleNode("*[1]", $root, true, "/^\s*(\d{1,2}:\d{2})\s*[A-Z]{3}\s*$/");
            $s->departure()
                ->code($this->http->FindSingleNode("*[1]", $root, true, "/^\s*\d{1,2}:\d{2}\s*([A-Z]{3})\s*$/"))
                ->date((!empty($date) && !empty($time)) ? strtotime($time, $date) : null)
            ;

            // Arrival
            $time = $this->http->FindSingleNode("*[3]", $root, true, "/^\s*(\d{1,2}:\d{2})\s*[A-Z]{3}\s*$/");
            $s->arrival()
                ->code($this->http->FindSingleNode("*[3]", $root, true, "/^\s*\d{1,2}:\d{2}\s*([A-Z]{3})\s*$/"))
                ->date((!empty($date) && !empty($time)) ? strtotime($time, $date) : null)
            ;
        }
    }

    private function t($s)
    {
        if (!isset($this->lang, self::$dictionary[$this->lang], self::$dictionary[$this->lang][$s])) {
            return $s;
        }

        return self::$dictionary[$this->lang][$s];
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

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));

        $in = [
            // 15/10/2024
            '/^\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\s*$/iu',
        ];
        $out = [
            '$1.$2.$3',
        ];

        $date = preg_replace($in, $out, $date);

        // $this->logger->debug('date end = ' . print_r( $date, true));

        return strtotime($date);
    }

    private function opt($field, $delimiter = '/')
    {
        $field = (array) $field;

        if (empty($field)) {
            $field = ['false'];
        }

        return '(?:' . implode("|", array_map(function ($s) use ($delimiter) {
            return str_replace(' ', '\s+', preg_quote($s, $delimiter));
        }, $field)) . ')';
    }
}
