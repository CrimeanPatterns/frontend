<?php

namespace AwardWallet\Engine\sagrada\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Event extends \TAccountChecker
{
	public $mailFiles = "sagrada/it-898433291.eml, sagrada/it-899517864.eml";
    public $subjects = [
        'Purchase summary by', // en
        'Resumen de tu compra en', // es
    ];

    public $lang = '';

    public $detectLang = [
        "en" => ["Purchase Confirmation"],
        "es" => ["Confirmación de compra"],
    ];


    public static $dictionary = [
        'en' => [
            'numPhrase' => ['This is your purchase confirmation. Your reservation number is:'],
        ],
        'es' => [
            'numPhrase' => ['Este e-mail es tu confirmación de compra. El número de localizador de reserva es el:'],
            'Purchase Confirmation' => ['Confirmación de compra'],
            'Visit details' => ['Detalles de tu visita'],
            'Your reservation number' => ['El número de localizador de reserva'],
            'Hello' => ['Hola'],
            'Total amount' => ['Total reserva'],
            'Amount' => ['Importe'],
            'Purchase details' => ['Detalles de tu compra'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], 'sagradafamilia.org') !== false) {
            foreach ($this->subjects as $subject) {
                if (stripos($headers['subject'], $subject) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        $this->assignLang();

        if ($this->http->XPath->query("//img[contains(@src, 'SagradaFamilia.')]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Purchase Confirmation'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->eq($this->t('Visit details'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Your reservation number'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]sagradafamilia\.org$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email): Email
    {
        $this->assignLang();

        $this->Event($email);
        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function Event(Email $email)
    {


        $eventNodes = $this->http->XPath->query("//tr[./td[{$this->eq($this->t('Visit details'))}]]/following-sibling::tr[normalize-space()][1]/descendant::td[2]/descendant::table");

        foreach ($eventNodes as $nodeRoot){
            $e = $email->add()->event();

            $e->type()
                ->event();

            $e->general()
                ->traveller($this->http->FindSingleNode("//text()[{$this->starts($this->t('Hello'))}]/following::text()[normalize-space()][1]", null, false, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/u"))
                ->confirmation($this->http->FindSingleNode("//text()[{$this->eq($this->t('numPhrase'))}]/following::text()[normalize-space()][1]", null, false, "/^([A-Z\d]{5,8})$/"));

            $e->place()
                ->name($this->http->FindSingleNode("./descendant::text()[normalize-space()][1]", $nodeRoot))
                ->address("Sagrada Familia, Carrer de Mallorca, 401 08013 Barcelona, Spain");

            $startDate = $this->http->FindSingleNode("./descendant::text()[normalize-space()][2]", $nodeRoot, false, "/^(\d{1,2}\/\d{1,2}\/\d{4}\s*[\d\:]+\s*A?P?M?)$/");

            $e->booked()
                ->start(strtotime($this->normalizeDate($startDate)))
                ->noEnd();

            $priceInfo = $this->http->FindSingleNode("//text()[{$this->starts($this->t('Total amount'))}]");

            if (preg_match("/^{$this->opt($this->t('Total amount'))}[ ]*\:[ ]*(?<price>[\d\.\,\']+)\s*(?<currency>\D{1,3})$/", $priceInfo, $m)
                || preg_match("/^{$this->opt($this->t('Total amount'))}[ ]*\:[ ]*(?<currency>\D{1,3})\s*(?<price>[\d\.\,\']+)$/", $priceInfo, $m)) {
                $currency = $this->normalizeCurrency($m['currency']);

                $e->price()
                    ->currency($currency)
                    ->total(PriceHelper::parse($m['price'], $currency));

                $priceNodes = $this->http->XPath->query("//text()[{$this->eq($this->t('Purchase details'))}]/following::table[{$this->contains($this->t('Amount'))}][1]/descendant::tr[normalize-space()][not(./td[{$this->eq($this->t('Amount'))}])]");
                $costArray = [];
                $adults = '';
                $infants = '';

                foreach ($priceNodes as $node){
                    $nodeName = $this->http->FindSingleNode("./descendant::td[normalize-space()][1]", $node);
                    $nodeValue = $this->http->FindSingleNode("./descendant::td[normalize-space()][3]", $node, false, '/^(?:\D{1,3})?\s*([\d\.\,\']+)\s*(?:\D{1,3})?$/');

                    if (preg_match("/([0-9]+)[ ]*x[ ]*General/", $nodeName, $m) && $nodeValue !== null){
                        $adults = $m[1];
                        $costArray[] = PriceHelper::parse($nodeValue, $currency);
                    } else if (preg_match("/([0-9]+)[ ]*x[ ]*Children/", $nodeName, $m)){
                        $infants = $m[1];
                    }
                }

                if (!empty($costArray)){
                    $e->price()
                        ->cost(array_sum($costArray));
                }
            }

            if (!empty($adults)){
                $e->booked()
                    ->guests($adults);
            }

            if (!empty($infants)){
                $e->booked()
                    ->kids($infants);
            }
        }
    }



    public static function getEmailLanguages()
    {
        return array_keys(self::$dictionary);
    }

    public static function getEmailTypesCount()
    {
        return count(self::$dictionary);
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
                return str_replace(' ', '\s+', preg_quote($s, '/'));
            }, $field)) . ')';
    }

    private function starts($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "starts-with(normalize-space(.), \"{$s}\")";
        }, $field));
    }

    private function normalizeCurrency($string = '')
    {
        if (empty($string)) {
            return $string;
        }
        $currences = [
            'GBP' => ['£'],
            'EUR' => ['€'],
        ];
        $string = trim($string);

        foreach ($currences as $currencyCode => $currencyFormats) {
            foreach ($currencyFormats as $currency) {
                if ($string === $currency) {
                    return $currencyCode;
                }
            }
        }

        return $string;
    }

    private function contains($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return implode(" or ", array_map(function ($s) {
            return "contains(normalize-space(.), \"{$s}\")";
        }, $field));
    }

    private function eq($field)
    {
        $field = (array) $field;

        if (count($field) == 0) {
            return 'false()';
        }

        return '(' . implode(" or ", array_map(function ($s) {
                return "normalize-space(.)=\"{$s}\"";
            }, $field)) . ')';
    }

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function normalizeDate($str)
    {
        $in = [
            // 16/05/2025 10:00
            "/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s*([\d\:]+\s*A?P?M?)$/",
        ];
        $out = [
            "$2/$1/$3 $4",
        ];

        $date = preg_replace($in, $out, $str);

        return $date;
    }

    private function assignLang()
    {
        foreach ($this->detectLang as $lang => $array) {
            foreach ($array as $value) {
                if ($this->http->XPath->query("//text()[{$this->contains($value)}]")->length > 0) {
                    $this->lang = $lang;

                    return true;
                }
            }
        }

        return false;
    }
}
