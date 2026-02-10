<?php

namespace AwardWallet\Engine\golair\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class TravelStatement extends \TAccountChecker
{
    public $mailFiles = "golair/statements/it-62321496.eml, golair/statements/it-62321529.eml, golair/statements/it-62327524.eml, golair/statements/it-62336003.eml, golair/statements/it-62742599.eml, golair/statements/it-73078804.eml, golair/statements/it-73124722.eml";
    public $lang = '';
    public static $dictionary = [
        'pt' => [
            'Número Smiles:' => ['Número Smiles:', 'Seu número Smiles:', 'Seu número Smiles é:'], // duplicated in parser golair/BilheteSmiles
        ],
        'es' => [
            'Número Smiles:' => ['Número Smiles:'], // depends on parser golair/TravelStatement
            'Olá,'           => 'Hola,', // depends on parser golair/TravelStatement
            'Categoria:'     => 'Categoría:', // depends on parser golair/TravelStatement
            'Saldo em'       => ['Saldo al', 'Saldo:'], // depends on parser golair/TravelStatement
        ],
    ];
    private $reFrom = ['.smiles.com.br', '.smiles.com.ar'];
    private $reProvider = [' Smiles', ' SMILES'];
    private $reSubject = [
        'Comunicado Smiles | Atualizações na Política de Privacidade Smiles',
        'Comunicado Smiles | Informações importantes sobre a parceria com a',
    ];
    private $reBody = [
        'pt' => [
            ['Seu número Smiles', 'Acesse a sua conta'],
            ['Número Smiles:', 'Acesse a sua conta'],
        ],
        'es' => [
            ['Número Smiles:', 'Entrá a tu cuenta'],
            ['Número Smiles:', 'Entrar a mi cuenta'],
        ],
    ];

    public static function parseStatement(Email $email, \TAccountChecker $checker, int $dateRelative): void
    {
        // used in parser golair/BilheteSmiles

        $st = $email->add()->statement();
        
        /** @var TravelStatement $checker */
        $numberText = $checker->http->FindSingleNode("(descendant::text()[" . self::contains($checker->t('Número Smiles:')) . " or " . self::contains(self::tlocal('Número Smiles:', $checker->lang)) . "]/ancestor::td[1])[1]");

        if ($number = $checker->http->FindPreg('/:\s*(\d+)$/', false, $numberText)) {
            $st->setNumber($number)
                ->setLogin($number)
                ->addProperty('Name', $checker->http->FindSingleNode("(//text()[" . self::contains($checker->t('Olá,')) . " or " . self::contains(self::tlocal('Olá,', $checker->lang)). "]/ancestor::td[1])[1]",
                    null, false, "/(?:" . self::opt($checker->t('Olá,')) ."|". self::opt(self::tlocal('Olá,', $checker->lang)) .")\s+([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]|[[:upper:]]\.?)[,!;) ]*$/u"))
                ->addProperty('Category', $checker->http->FindSingleNode("(//text()[" . self::contains($checker->t('Categoria:')) . " or " . self::contains(self::tlocal('Categoria:', $checker->lang)) . "]/ancestor::td[1])[1]",
                    null, false, '/:\s*(\w{3,15})$/'));

            $balanceRow = $checker->http->FindSingleNode("descendant::tr[not(.//tr[normalize-space()])][" . self::contains($checker->t('Saldo em')) . " or " . self::contains(self::tlocal('Saldo em', $checker->lang)) . "][1]");

            if (preg_match("/^{$checker->opt($checker->t('Saldo em'))}[: ]+(?<date>\d{1,2}\/\d{1,2})[: ]+(?:de[ ]+)?(?<amount>\d[,.\'\d ]*)(?:\s*[[:alpha:]]|$)/u", $balanceRow, $m)) {
                // Saldo em 20/07: 46.100 milhas    |    Saldo em 07/03 de 47 milhas
                $date = self::normalizeDate($m['date']);
                $st->parseBalanceDate($date, $dateRelative, '%D%/%Y%', false);
                $st->setBalance(preg_replace('/\D+/', '', $m['amount']));
            } elseif (preg_match("/^{$checker->opt($checker->t('Saldo em'))}[: ]+(?<date>\d{1,2}\/\d{1,2}\/\d{4})[: ]+(?:de[ ]+)?(?<amount>\d[,.\'\d ]*)(?:\s*[[:alpha:]]|$)/u", $balanceRow, $m)) {
                // Saldo em 25/05/2022: 57090 milhas
                $date = strtotime(self::normalizeDate($m['date']));
                $st->setBalanceDate($date);
                $st->setBalance(preg_replace('/\D+/', '', $m['amount']));
            }
        } elseif ($number = $checker->http->FindPreg('/:\s*(\d{5,})\s+(?:(?:' . self::opt($checker->t('Saldo em')) .'|'. self::opt(self::tlocal('Saldo em', $checker->lang)) .')|Entrar a mi cuenta)/', false, $numberText)) {
            $st->setNumber($number)
                ->setLogin($number)
                ->addProperty('Name', $checker->http->FindSingleNode("(//text()[{$checker->starts($checker->t('Olá,'))}])[1]", null, false, "/(?:" . self::opt($checker->t('Olá,')) . "|" . self::opt(self::tlocal('Olá,', $checker->lang)) . ")\s+([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]|[[:upper:]]\.?)[,!;) ]*$/u"))
            ;

            if ($date = $checker->http->FindSingleNode("//text()[{$checker->contains($checker->t('Saldo em'))}]/ancestor::*[1]", null, false, "/(?:" . self::opt($checker->t('Saldo em')) ."|". self::opt(self::tlocal('Saldo em', $checker->lang)) .")\s+(\d[\d\- :\.\/]{4,}):/")) {
                $date = self::normalizeDate($date);

                if (preg_match("/\b\d{4}\b/", $date)) {
                    $st->setBalanceDate(strtotime($date));
                } else {
                    $st->parseBalanceDate($date, $dateRelative, '%D%/%Y%', false);
                }
                $balance = $checker->http->FindSingleNode("(//text()[{$checker->contains($checker->t('Saldo em'))}]/ancestor::*[1])[1]",
                    null, false, "/(?:" . self::opt($checker->t('Saldo em')) ."|". self::opt(self::tlocal('Saldo em', $checker->lang)) .")\s+\d[\d\- :\.\/]{4,}\s*:\s*(\d[,.\'\d\s]*)$/u");
                $st->setBalance(preg_replace('/\D+/', '', $balance));
            } else {
                $st->setNoBalance(true);
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (($dateRelative = strtotime($parser->getDate()))) {
            $this->parseStatement($email, $this, $dateRelative);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function detectEmailFromProvider($from)
    {
        return $this->arrikey($from, $this->reFrom) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if (!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true) {
            return false;
        }

        return array_key_exists('subject', $headers) && $this->arrikey($headers['subject'], $this->reSubject) !== false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if ($this->http->XPath->query("//text()[" . self::contains($this->reProvider) . "]")->length === 0) {
            return false;
        }

        return $this->assignLang()
            && $this->http->XPath->query('//tr[count(*[not(.//td) and not(.//th) and contains(translate(normalize-space(),"0123456789：","dddddddddd:"),"d:dd")])>1]')->length === 0; // for parser golair/BilheteSmiles
    }

    public static function getEmailLanguages()
    {
        return ['pt', 'es'];
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    public static function tlocal(string $phrase, $lang)
    {
        if (!isset(self::$dictionary, $lang) || empty(self::$dictionary[$lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$lang][$phrase];
    }

    private function assignLang(): bool
    {
        foreach ($this->reBody as $lang => $values) {
            foreach ($values as $value) {
                if ($this->http->XPath->query("//text()[" . self::contains($value[0]) . "]")->length > 0
                    && $this->http->XPath->query("//text()[" . self::contains($value[1]) . "]")->length > 0
                ) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeDate(?string $text): string
    {
        if (!is_string($text) || empty($text)) {
            return '';
        }
        $in = [
            // 31/12/2018 14:05
            '/^\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}:\d{1,2})\s*$/',
            // 28/12
            '/^(\d{1,2})\/(\d{1,2})$/',
            '/^\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\s*$/',
        ];
        $out = [
            '$3-$2-$1 $4',
            '$2/$1',
            '$3-$2-$1',
        ];

        return preg_replace($in, $out, $text);
    }

    private function t(string $phrase)
    {
        if (!isset(self::$dictionary, $this->lang) || empty(self::$dictionary[$this->lang][$phrase])) {
            return $phrase;
        }

        return self::$dictionary[$this->lang][$phrase];
    }

    public static function contains($field, string $node = ''): string
    {
        $field = (array)$field;
        if (count($field) === 0)
            return 'false()';
        return '(' . implode(' or ', array_map(function($s) use($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';
            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    public static function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ')="' . $s . '"';
        }, $field)) . ')';
    }

    public static function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'starts-with(normalize-space(' . $node . '),"' . $s . '")';
        }, $field)) . ')';
    }

    public static function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return preg_quote($s, '/');
        }, $field)) . ')';
    }

    private function arrikey($haystack, array $arrayNeedle)
    {
        foreach ($arrayNeedle as $key => $needles) {
            if (is_array($needles)) {
                foreach ($needles as $needle) {
                    if (stripos($haystack, $needle) !== false) {
                        return $key;
                    }
                }
            } else {
                if (stripos($haystack, $needles) !== false) {
                    return $key;
                }
            }
        }

        return false;
    }
}
