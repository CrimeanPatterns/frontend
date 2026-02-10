<?php

namespace AwardWallet\Engine\unidas\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class RentalTrip extends \TAccountChecker
{
    public $mailFiles = "unidas/it-900744833.eml";

    public $lang = 'pt';

    public $detectSubjects = [
        'pt' => [
            'Sua reserva com a Unidas está confirmada. nº',
        ],
    ];

    public static $dictionary = [
        'pt' => [
        ],
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]unidas\.com\.br$/', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        // detect Provider
        if ((empty($headers['from']) || stripos($headers['from'], 'unidas.com.br') === false)
            && stripos($headers['subject'], 'Unidas') === false
        ) {
            return false;
        }

        // detect Format
        foreach ($this->detectSubjects as $detectSubjects) {
            foreach ($detectSubjects as $dSubjects) {
                if (stripos($headers['subject'], $dSubjects) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Unidas'))}]")->length === 0
            && $this->http->XPath->query("//a/@href[{$this->contains('unidas.com.br')}]")->length === 0
            && $this->http->XPath->query("//img/@src[{$this->contains('unidas.com.br')}]")->length === 0
        ) {
            return false;
        }

        // detect Format
        if ($this->http->XPath->query("//text()[{$this->starts($this->t('Documento:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Reserva nº'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Horário de funcionamento'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Diárias:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Retirada:'))}]")->length > 0
        ) {
            return true;
        }

        return false;
    }

    public function parseRental(Email $email)
    {
        $r = $email->add()->rental();

        $patterns = [
            'time' => '\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?)?', // 4:19PM    |    2:00 p. m.
        ];

        $nonEmptyXpath = "(string-length(normalize-space()) > 1)";

        // collect reservation confirmation
        $confirmationText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Reserva nº'))}]/ancestor::td[1][normalize-space()]");

        if (preg_match("/^\s*(?<desc>{$this->opt($this->t('Reserva nº'))})\s*(?<number>\d+)\s*{$this->opt($this->t('Status'))}\s*(?<status>\w+)\s*$/", $confirmationText, $m)) {
            $r->general()
                ->confirmation($m['number'], $m['desc'])
                ->status($m['status']);
        }

        // collect traveller
        $traveller = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Documento:'))}]/preceding::text()[normalize-space()][1]", null, true, "/^\s*([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])\s*$/u");

        if (!empty($traveller)) {
            $r->general()->traveller($traveller);
        }

        // collect pick-up address and datetime
        $pickUpText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Retirada:'))}]/ancestor::td[1][normalize-space()]/descendant::text()[normalize-space()][not({$this->eq($this->t('Retirada:'))})]"));
        $dropOffText = implode("\n", $this->http->FindNodes("//text()[{$this->eq($this->t('Devolução'))}]/ancestor::td[1][normalize-space()]/descendant::text()[normalize-space()][not({$this->eq($this->t('Devolução'))})]"));

        // pickUpText and dropOffText example:
        /*
            Ponta Grossa, Ponta Grossa - Parana
            05/06/2023
            08:00
            Av Visconde De Maua, 2143, Oficinas
        */

        $pickUpAndDropOffPattern = "/^"
            . "\s*(?<location1>.+?)[ ]*\n"
            . "[ ]*(?<date>\d+\/\d+\/\d{4})[ ]*\n"
            . "[ ]*(?<time>{$patterns['time']})[ ]*\n"
            . "[ ]*(?<location2>[\s\S]+?)\s*"
            . "$/u";

        if (preg_match($pickUpAndDropOffPattern, $pickUpText, $m)) {
            $r->pickup()
                ->location(trim($m['location1'] . ', ' . preg_replace("/\n/", ', ', $m['location2']), ', '))
                ->date($this->normalizeDate($m['date'] . ', ' . $m['time']));
        }

        if (preg_match($pickUpAndDropOffPattern, $dropOffText, $m)) {
            $r->dropoff()
                ->location(trim($m['location1'] . ', ' . preg_replace("/\n/", ', ', $m['location2']), ', '))
                ->date($this->normalizeDate($m['date'] . ', ' . $m['time']));
        }

        // collect pickUp and dropOff opening hours
        $pickUpOpeningHoursRows = $this->http->FindNodes("(//text()[{$this->eq($this->t('Horário de funcionamento'))}])[1]/ancestor::td[1]/descendant::text()[normalize-space()][not({$this->contains($this->t('Horário de funcionamento'))})]");

        if (!empty($pickUpOpeningHoursRows)) {
            $r->setPickUpOpeningHours($pickUpOpeningHoursRows);
        }

        $dropOffOpeningHoursRows = $this->http->FindNodes("(//text()[{$this->eq($this->t('Horário de funcionamento'))}])[last()]/ancestor::td[1]/descendant::text()[normalize-space()][not({$this->contains($this->t('Horário de funcionamento'))})]");

        if (!empty($dropOffOpeningHoursRows)) {
            $r->setDropOffOpeningHours($dropOffOpeningHoursRows);
        }

        // collect car type, car model and car image url
        $carImageXpath = "{$this->eq('imagem do carro selecionado', '@alt')} or {$this->contains('/images/grupos/', '@src')}";

        $carImageUrl = $this->http->FindSingleNode("//img[$carImageXpath]/@src");

        if (!empty($carImageUrl)) {
            $r->setCarImageUrl($carImageUrl);
        }

        $carRows = $this->http->FindNodes("//img[$carImageXpath]/following::table[1][{$this->contains($this->t('|'))}]/descendant::text()[normalize-space()][$nonEmptyXpath]"); //[not({$this->eq(mb_chr(0x00AD))})]

        if (count($carRows) === 3) {
            $r->car()->type($carRows[0] . ' ' . $carRows[1])
                ->model($carRows[2]);
        }

        // collect provider phones
        $phoneText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Central de Reservas'))}]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/(?<desc>{$this->opt($this->t('Central de Reservas'))})\s*\:\s*(?<phone>[\+\-\(\)\d ]+)\s*$/u", $phoneText, $m)) {
            $r->program()
                ->phone($m['phone'], $m['desc']);
        }

        $phoneText2 = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Serviço de Atendimento ao Cliente:'))}]/ancestor::tr[1][normalize-space()]");

        if (preg_match("/(?<desc>{$this->opt($this->t('Serviço de Atendimento ao Cliente:'))})\s*(?<phone>[\+\-\(\)\d ]+)\s+/u", $phoneText2, $m)) {
            $r->program()
                ->phone($m['phone'], trim($m['desc'], ':'));
        }

        // collect pricing details
        // note: in addition to spaces, string $totalText and other pricing strings contain soft hyphen symbols (U+00AD)
        // collect total
        $totalText = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Status do Pagamento:'))}]/ancestor::td[1]/following::text()[$nonEmptyXpath][1]");

        if (preg_match("/^[\s\x{00AD}]*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)[\s\x{00AD}]*$/u", $totalText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            // PriceHelper not parsed amount with more than two digit after decimal separator
            $amount = $this->re("/^(.+?[\.\,]\d{2})0+$/", $m['amount']) ?? $m['amount'];
            $total = PriceHelper::parse($amount, $currency);

            if ($total !== null) {
                $r->price()
                    ->total(PriceHelper::parse($amount, $currency))
                    ->currency($currency);
            }
        }

        // collect cost
        $costText = $this->http->FindSingleNode("//text()[{$this->contains($this->t('Diária(s)'))}]/ancestor::td[1]/following::text()[$nonEmptyXpath][1]");

        if (preg_match("/^[\s\x{00AD}]*(?<currency>[^\d\s]{1,3})\s*(?<amount>[\d\.\,\']+)[\s\x{00AD}]*$/u", $costText, $m)) {
            $currency = $this->normalizeCurrency($m['currency']);
            $amount = $this->re("/^(.+?[\.\,]\d{2})0+$/", $m['amount']) ?? $m['amount'];
            $cost = PriceHelper::parse($amount, $currency);

            if ($cost !== null) {
                $r->price()
                    ->cost(PriceHelper::parse($amount, $currency));
            }
        }

        // collect fees
        if (!empty($currency)) {
            $feeNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Proteção e Acessórios:'))}]/following::*[ count(*[$nonEmptyXpath]) = 2 and *[2][not({$this->contains($this->t('('))})] ][following::text()[{$this->eq($this->t('Valor total:'))}]]");

            foreach ($feeNodes as $feeRoot) {
                $feeName = trim($this->http->FindSingleNode("./descendant::td[$nonEmptyXpath][1]", $feeRoot), ':');
                $amount = $this->http->FindSingleNode("./descendant::td[$nonEmptyXpath][2]", $feeRoot, true, "/^\D*([\d\.\,\']+)[\s\x{00AD}]*$/");

                $amount = $this->re("/^(.+?[\.\,]\d{2})0+$/", $amount) ?? $amount;
                $feeAmount = PriceHelper::parse($amount, $currency);

                if ($feeAmount !== null) {
                    $r->price()
                        ->fee($feeName, $feeAmount);
                }
            }
        }
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->parseRental($email);
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

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function eq($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            return 'normalize-space(' . $node . ')="' . $s . '"';
        }, $field)) . ')';
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

    private function normalizeDate(?string $date): ?int
    {
        if (empty($date)) {
            return null;
        }

        $in = [
            "/^(\d+)\/(\d+)\/(\d{4})\s*\,\s*(\d+\:\d+)$/", // 28/03/2025, 23:45 => 28.03.2025, 23:45
        ];
        $out = [
            '$1.$2.$3, $4',
        ];

        return strtotime(preg_replace($in, $out, $date));
    }

    private function normalizeCurrency($s)
    {
        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
            'R$'         => 'BRL',
            '$'          => '$',
        ];

        if ($code = $this->re("#(?:^|\s)([A-Z]{3}\D)(?:$|\s)#", $s)) {
            return $code;
        }

        $s = $this->re("#([^\d\,\.]+)#", $s);

        foreach ($sym as $f => $r) {
            if (strpos($s, $f) !== false) {
                return $r;
            }
        }

        return $s;
    }
}
