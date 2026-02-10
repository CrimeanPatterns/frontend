<?php

namespace AwardWallet\Engine\redroof\Email\Statement;

use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Promo extends \TAccountChecker
{
    public $mailFiles = "redroof/statements/it-65251342.eml, redroof/statements/it-87160325.eml, redroof/statements/it-927343695.eml, redroof/statements/it-927478371.eml";

    public function detectEmailByHeaders(array $headers)
    {
        return strpos($headers['subject'], 'Welcome to RediRewards') !== false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        if (strpos($parser->getHTMLBody(), 'RediRewards Member Services') === false) {
            return false;
        }

        return $this->findRoot1()->length === 1 || $this->findRoot2()->length === 1;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]redroof\.com$/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $patterns['travellerName'] = '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]';

        $st = $email->add()->statement();

        $roots1 = $this->findRoot1();

        if ($roots1->length === 1) {
            $this->logger->debug('Found root1.');
            $root1 = $roots1->item(0);

            $rootText = $this->htmlToText($this->http->FindHTMLByXpath('.', null, $root1));

            /*
                Hi Zhichao,
                Account# 6005192374 as of 08/31/2020
                Points: 1444
            */
            $pattern1 = "/^\s*(Hi|Hello|Welcome)[ ]+(?<name>{$patterns['travellerName']})[ ,:;!?]*\n+[ ]*Account#[ ]*(?<number>\d{5,})[ ]+as of[ ]+(?<date>.{6,}?)[ ]*\n+[ ]*Points[: ]+(?<balance>\d+)\s*$/";

            /*
                Welcome Patti,
                Account #: 6005148373
                Member Services: 800.333.0991
            */
            $pattern2 = "/^\s*(Hi|Hello|Welcome)[ ]+(?<name>{$patterns['travellerName']})[ ,:;!?]*\n+[ ]*Account #[: ]*(?<number>\d{5,})[ ]*\n+[ ]*Member Services/";

            if (preg_match($pattern1, $rootText, $m)) {
                // it-65251342.eml
                $st->addProperty('Name', $m['name'])->setNumber($m['number'])->parseBalanceDate($m['date'])->setBalance($m['balance']);

                return $email;
            } elseif (preg_match($pattern2, $rootText, $m)) {
                // it-87160325.eml
                $st->addProperty('Name', $m['name'])->setNumber($m['number'])->setNoBalance(true);

                return $email;
            }
        }

        $roots2 = $this->findRoot2();

        if ($roots2->length === 1) {
            $this->logger->debug('Found root2.');
            $root2 = $roots2->item(0);

            $name = $balance = null;

            $name = $this->http->FindSingleNode("preceding::text()[starts-with(normalize-space(),'Hi ')][1]", $root2, true, "/^Hi \s*({$patterns['travellerName']})(?:\s*[.;!]|$)/u");

            $balance = $this->http->FindSingleNode("preceding::text()[contains(normalize-space(),' pts')][1]", $root2, true, "/^(\d[,.\d ]*?)\s*pts[\s.;!]*$/i");

            if ($name) {
                $st->addProperty('Name', $name);
            }

            if ($balance !== null) {
                $st->setBalance($balance);
            } elseif ($name) {
                $st->setNoBalance(true);
            }
        }

        return $email;
    }

    public static function getEmailTypesCount()
    {
        return 0;
    }

    private function findRoot1(): \DOMNodeList
    {
        // examples: it-65251342.eml, it-87160325.eml
        return $this->http->XPath->query("//tr/*[normalize-space()][1][ not(.//tr) and descendant::text()[starts-with(normalize-space(),'Account#') or starts-with(normalize-space(),'Account #')] ]");
    }

    private function findRoot2(): \DOMNodeList
    {
        // examples: it-927343695.eml, it-927478371.eml
        return $this->http->XPath->query("//a[ normalize-space()='DEALS' and following::a[normalize-space()][1][normalize-space()='BOOK'] ]");
    }

    private function htmlToText(?string $s, bool $brConvert = true): string
    {
        if (!is_string($s) || $s === '') {
            return '';
        }
        $s = str_replace("\r", '', $s);
        $s = preg_replace('/<!--.*?-->/s', '', $s); // comments

        if ($brConvert) {
            $s = preg_replace('/\s+/', ' ', $s);
            $s = preg_replace('/<[Bb][Rr]\b.*?\/?>/', "\n", $s); // only <br> tags
        }
        $s = preg_replace('/<[A-z][A-z\d:]*\b.*?\/?>/', '', $s); // opening tags
        $s = preg_replace('/<\/[A-z][A-z\d:]*\b[ ]*>/', '', $s); // closing tags
        $s = html_entity_decode($s);
        $s = str_replace(chr(194) . chr(160), ' ', $s); // NBSP to SPACE

        return trim($s);
    }
}
