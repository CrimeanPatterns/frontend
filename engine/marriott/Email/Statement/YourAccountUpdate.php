<?php

namespace AwardWallet\Engine\marriott\Email\Statement;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;

class YourAccountUpdate extends \TAccountChecker
{
    public $mailFiles = "marriott/it-108334029.eml, marriott/it-109069353.eml, marriott/statements/it-62812090.eml, marriott/statements/it-62919390.eml, marriott/statements/it-62938723.eml, marriott/statements/it-64401491.eml, marriott/statements/it-64415901.eml, marriott/statements/it-76376494.eml, marriott/statements/it-76377408.eml, marriott/statements/it-902883240.eml, marriott/statements/it-902893076.eml, marriott/statements/it-903650402.eml, marriott/statements/it-929491771.eml, marriott/statements/it-929623106.eml, marriott/statements/it-929798988.eml";

    private $subjects = [
        'en' => [
            'Account Update:',
            'Your Temporary Code Request',
            'Your Marriott Bonvoy Member Number',
            'Your Password Request Has Been Received',
            'Marriott Rewards Account Created', // it-903650402.eml
            'Online Access Created For Your Marriott Bonvoy Account', // it-902883240.eml
        ],
    ];

    private $membershipPhrases = [
        'Your Marriott Rewards® online account is waiting for you', // it-903650402.eml
        'Your Marriott Rewards(R) online account is waiting for you',
        'Your Marriott Rewards online account is waiting for you',
        'Türkiye and Syria. Marriott Bonvoy',
        'verification on your Marriott Bonvoy account',
        'Marriott Bonvoy™ profile has been updated',
        'Su cuenta por Internet de Marriott Rewards® lo espera', // es
        'Su cuenta por Internet de Marriott Rewards(R) lo espera', // es
        'Su cuenta por Internet de Marriott Rewards lo espera', // es
        'Sua conta on-line Marriott Rewards® está esperando por você', // pt
        'Sua conta on-line Marriott Rewards(R) está esperando por você', // pt
        'Sua conta on-line Marriott Rewards está esperando por você', // pt
        'password request has been received. Please reset your password immediately using this secure link', // it-62938723.eml
        'Reimpostate subito la vostra password utilizzando questo link sicuro', // it
        '万豪旅享家”账户密码请求。请使用以下安全链接立即重置您的密码', // zh
        '. Restablezca su contraseña de inmediato usando este vínculo seguro', // es
        'foi recebida. Redefina a sua senha imediatamente usando este link seguro', // pt
        'ist bei uns eingegangen. Bitte setzen Sie Ihr Passwort über diesen sicheren Link sofort zurück', // de
        'a bien été reçue. Veuillez réinitialiser immédiatement votre mot de passe en cliquant sur ce lien sécurisé', // fr
    ];

    private $otcPhrases = [
        'You recently requested a temporary code in order to complete an Account transaction. To authorize and proceed with your transaction, please enter the following code on your screen',
        'Recientemente ha solicitado un código de acceso provisional para completar una transacción de su cuenta. Para autorizar y continuar con su transacción, introduzca el siguiente código en la pantalla', // es
        'Você pediu recentemente um código de acesso temporário para concluir uma transação na sua conta. Para autorizar e prosseguir com sua transação, digite o seguinte código em sua tela', // pt
        'Sie haben vor Kurzem einen vorübergehenden Code angefordert, um eine Konto-Transaktion abzuschließen. Für die Autorisierung und um mit der Transaktion fortfahren zu können, geben Sie bitte den folgenden Code auf Ihrem Bildschirm ein', // de
        "Vous avez récemment fait une demande pour obtenir un code temporaire afin d'effectuer une transaction depuis votre compte. Pour approuver votre demande et procéder à la transaction, veuillez saisir le code suivant", // fr
        '您近期申请了临时验证码以完成帐户交易。 如需授权并继续交易，请在屏幕上输入下列验证码', // zh
        '최근에 계정 거래 완료를 위해 임시 코드를 요청하셨습니다. 거래를 진행하시려면, 화면에 코드', // ko
        'お客様は、アカウントのお取引を完了するための仮コードをリクエストされました。アカウントのお取引を承諾し、続行するには、スクリーンに表示されている次のコードを入力してください', // ja
    ];

    private $patterns = [
        'travellerName' => '[[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]]', // Mr. Hao-Li Huang
    ];

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[-.@]marriott\.com$/i', $from) > 0;
    }

    public function detectEmailByHeaders(array $headers)
    {
        if ((!array_key_exists('from', $headers) || $this->detectEmailFromProvider(rtrim($headers['from'], '> ')) !== true)
            && (!array_key_exists('subject', $headers) || strpos($headers['subject'], 'Marriott') === false)
        ) {
            return false;
        }

        foreach ($this->subjects as $phrases) {
            foreach ((array) $phrases as $phrase) {
                if (is_string($phrase) && stripos($headers['subject'], $phrase) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if ($this->detectEmailFromProvider($parser->getCleanFrom()) !== true
            && $this->http->XPath->query('//a[contains(@href,"email-marriott.com/")] | //node()[contains(.,"www.marriott.com") or contains(normalize-space(),"unsubscribe from Marriott")]')->length === 0
        ) {
            return false;
        }

        if (empty($textPlain = $parser->getPlainBody())) {
            $textPlain = $parser->getHTMLBody();
        }

        return $this->isMembership($textPlain)
            || $this->parseYourNumber($textPlain) || $this->parseAccessCreated($textPlain)
            || $this->findRoot1()->length === 1 || $this->findRoot2()->length === 1
            || $this->findRoot3()->length === 1 || $this->findRoot4()->length === 1
            || $this->findRoot5()->length === 1 || $this->findRoot6()->length === 1
            || $this->findRoot7()->length === 1 || $this->findRoot8()->length === 1
            ;
    }

    public function ParsePlanEmailExternal(\PlancakeEmailParser $parser, Email $email)
    {
        if (empty($textPlain = $parser->getPlainBody())) {
            $textPlain = $this->http->Response['body'];
        }

        $this->parseOTC($email, $textPlain);

        $st = $email->add()->statement();

        if ($this->isMembership($textPlain)) {
            $st->setMembership(true);

            return $email;
        }

        $status = $name = $balance = $number = $isMembership = null;

        // it-62812090.eml, it-76377408.eml, it-76376494.eml
        $roots1 = $this->findRoot1();

        if ($roots1->length === 1) {
            $this->logger->debug('Found root1.');
            $root1 = $roots1->item(0);
            $headerText = $this->http->FindSingleNode('.', $root1);

            if (preg_match("/^([^|]{3,}?)\s*\|\s*([- ]*\d[,.\'\d ]*?)\s*point/i", $headerText, $m)) {
                // Silver  |  359320 Points    or    Silver Elite  |  -47,455 Points
                $status = $m[1];
                $balance = $m[2];
            }
        }

        // it-64401491.eml
        $roots2 = $this->findRoot2();

        if ($roots2->length === 1) {
            $this->logger->debug('Found root2.');
            $root2 = $roots2->item(0);

            if ($roots1->length === 0) {
                $st->setNoBalance(true);
            }

            $name = $this->http->FindSingleNode('tr[1]', $root2, true, '/^[[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]$/u');

            $number = $this->http->FindSingleNode('tr[2]', $root2, true, '/^([X\d]{5,})\s*|.+$/i');
        }

        // it-64415901.eml
        $roots3 = $this->findRoot3();

        if ($roots3->length === 1) {
            $this->logger->debug('Found root3.');
            $root3 = $roots3->item(0);

            $name = implode(' ', $this->http->FindNodes("ancestor::*[ preceding-sibling::*[normalize-space()] ][1]/preceding-sibling::*[normalize-space()]/descendant::node()[normalize-space()='member name']/following::tr[normalize-space()][1]/descendant::text()[normalize-space()]", $root3));

            if (!$name) {
                $name = implode(' ', $this->http->FindNodes("ancestor::*[ preceding-sibling::*[normalize-space()] ][1]/preceding-sibling::*[normalize-space()][last()]/descendant::tr[not(.//tr) and normalize-space()][1]/descendant::text()[normalize-space()]", $root3));
            }

            if (!preg_match('/^[[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]$/u', $name)) {
                $name = null;
            }

            $number = $this->http->FindSingleNode("*[1]/descendant::tr[ *[1][normalize-space()='ACCOUNT'] ]/*[position()>1]", $root3, true, '/^[X\d]{5,}$/');

            $balance = $this->http->FindSingleNode("*[1]/descendant::tr[ *[1][normalize-space()='POINTS'] ]/*[position()>1]", $root3, true, '/^[- ]*\d[,.\'\d ]*$/');

            if ($balance === null) {
                $st->setNoBalance(true);
            }

            $status = $this->http->FindSingleNode("*[2]/descendant::tr[ *[1][normalize-space()='STATUS'] ]/*[position()>1]", $root3);
        }

        // it-109069353.eml
        $roots4 = $this->findRoot4();
//        0 points                                      Member                                   XXXXX6097
//        Tormund Reed                                                                        0
//        You’re 10 nights from Marriott Bonvoy® Silver Elite status.                   Nights This Year
//        » My benefits                                                             » Book your first stay
        if ($roots4->length === 1) {
            $this->logger->debug('Found root4.');
            $root4 = $roots4->item(0);

            $name = $this->http->FindSingleNode("following::td[not(.//td)][normalize-space()][1][following::text()[normalize-space()][1][starts-with(normalize-space(), 'You’re')]]", $root4);

            if (!preg_match('/^[[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]$/u', $name)) {
                $name = null;
            }

            $number = $this->http->FindSingleNode("*[3]", $root4, true, '/^[X\d]{5,}$/');

            $balance = $this->http->FindSingleNode("*[1]", $root4, true, '/^\s*([\d, ]+) points?\s*$/');

            $status = trim($this->http->FindSingleNode("*[2]", $root4), '|');

            $nights = $this->http->FindSingleNode("following::text()[normalize-space() = 'Nights This Year' or normalize-space() = 'Night This Year'][1]/preceding::text()[normalize-space()][1]", $root4, true, "/^\s*(\d+)\s*$/");
            $st->addProperty('Nights', $nights);
        }

        // it-108334029.eml
        $roots5 = $this->findRoot5();
//              Tormund Reed
//      Member | 11018 POINTS | 3 NIGHTS
//      » view ACTIVITY   » SEE BENEFITS
        if ($roots5->length === 1) {
            $this->logger->debug('Found root5.');
            $root5 = $roots5->item(0);

            $name = $this->http->FindSingleNode("preceding::text()[normalize-space()][1]", $root5);

            if (!preg_match('/^[[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]]$/u', $name)) {
                $name = null;
            }

            $info = $this->http->FindSingleNode("self::text()", $root5);

            if (preg_match("/^\s*([[:alpha:] ]+?)\s*\|\s*(\d+) POINTS?\s*\|\s*(\d+) NIGHTS?\s*$/", $info, $m)) {
                $isMembership = true;
                $balance = $m[2];
                $status = $m[1];
                $st->addProperty('Nights', $m[3]);
            }
        }

        //it-929798988.eml
        $roots6 = $this->findRoot6();

        if ($roots6->length === 1) {
            $this->logger->debug('Found root6.');
            $root6 = $roots6->item(0);
            $name = $this->http->FindSingleNode("preceding::text()[string-length()>2][1]", $root6);
            $st->setNoBalance(true);
        }

        //it-929491771.eml
        $roots7 = $this->findRoot7();

        if ($roots7->length === 1) {
            $this->logger->debug('Found root7.');
            $root7 = $roots7->item(0);
            $name = $this->http->FindSingleNode("./preceding::text()[string-length()>2][1]", $root7);
            $accountInfo = $this->http->FindSingleNode(".", $root7);

            if (preg_match("/^(?<level>\D+)\s+\|\s+POINTS[\s\|]+(?:(?<nights>\d+)\s*nights?)?$/", $accountInfo, $m)
                || preg_match("/^(?<level>\D+)\s+\|\s+(?<balance>[\d\,]+)\s*POINTS[\s\|]+(?:(?<nights>\d+)\s*nights?)?$/", $accountInfo, $m)) {
                if (isset($m['balance'])) {
                    $balance = $m['balance'];
                } else {
                    $st->setNoBalance(true);
                }
                $status = $m['level'];

                if (isset($m['nights']) && $m['nights'] !== null) {
                    $st->addProperty('Nights', $m['nights']);
                }
            }
        }
        // it-929623106.eml
        $roots8 = $this->findRoot8();

        if ($roots8->length === 1) {
            $this->logger->debug('Found root8.');
            $root8 = $roots8->item(0);

            $name = $this->http->FindSingleNode("./preceding::text()[string-length()>2][2]", $root8);
            $number = $this->http->FindSingleNode("./preceding::text()[string-length()>2][1]", $root8, true, "/^([X]+\s*\d{3,})$/");
            $accountInfo = $this->http->FindSingleNode(".", $root8);

            if (preg_match("/^(?<balance>[\d\,]+)\s*points as of\s*(?<dateBalance>\d+[\/\.]\d+[\/\.]\d{4})/", $accountInfo, $m)) {
                if ($m['balance'] !== null) {
                    $balance = $m['balance'];

                    $dateBalance = $this->normalizeDate($m['dateBalance']);
                    $dateEmail = strtotime($parser->getDate());

                    if ($dateBalance > $dateEmail) {
                        $st->setBalanceDate($this->normalizeDate($m['dateBalance'], true));
                    } else {
                        $st->setBalanceDate($dateBalance);
                    }
                } else {
                    $st->setNoBalance(true);
                }
            }
        }

        if ($number === null && $this->parseYourNumber($textPlain, $number)) {
            // it-902893076.eml
            $st->setNumber($number)->setLogin($number);

            if ($balance === null) {
                $st->setNoBalance(true);

                return $email;
            }
        }

        if ($name === null && $this->parseAccessCreated($textPlain, $name)) {
            // it-902883240.eml
            $st->addProperty('Name', $name);

            if ($balance === null) {
                $st->setNoBalance(true);

                return $email;
            }
        }

        if (preg_match('/^[X]{3,}(\d+)$/i', $number, $m)) {
            // XXXXX6297
            $st->setNumber($m[1])->masked()
                ->setLogin($m[1])->masked();
        } elseif ($roots2->length === 1 || $roots3->length === 1) {
            // 143926297
            $st->setNumber($number)->setLogin($number);
        } elseif ($isMembership) {
            $st->setMembership(true);
        }

        if ($name !== null) {
            $st->addProperty('Name', $name);
        }

        if ($status !== null) {
            $st->addProperty('Level', $status);
        }

        if ($balance !== null) {
            if (substr($balance, 0, 1) === '-') {
                // it-76376494.eml
                $st->setNoBalance(true);
            } else {
                $st->setBalance(PriceHelper::parse($balance));
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
        return $this->http->XPath->query("//tr[ descendant::text()[normalize-space()='My Account'] ]/following-sibling::tr[normalize-space()][1][contains(.,'|')]");
    }

    private function findRoot2(): \DOMNodeList
    {
        return $this->http->XPath->query("//*[ tr[1] and tr[2]/descendant::text()[starts-with(normalize-space(),'My Benefits')] ]");
    }

    private function findRoot3(): \DOMNodeList
    {
        return $this->http->XPath->query("//tr[ count(*)=2 and *[1]/descendant::text()[normalize-space()='ACCOUNT'] and *[2]/descendant::text()[normalize-space()='STATUS'] ]");
    }

    private function findRoot4(): \DOMNodeList
    {
        return $this->http->XPath->query("//tr[count(*) = 3 and not(.//tr)][*[1][contains(., 'point')] and *[3][starts-with(normalize-space(), 'XXXX')]]");
    }

    private function findRoot5(): \DOMNodeList
    {
        return $this->http->XPath->query("//text()[contains(., 'POINTS') and contains(., 'NIGHTS') and contains(., '|')]");
    }

    private function findRoot6(): \DOMNodeList
    {
        return $this->http->XPath->query("//text()[contains(normalize-space(), 'with')]/ancestor::td[1][contains(normalize-space(), 'Marriott') and contains(normalize-space(), 'Bonvoy')]/preceding::text()[normalize-space()='thank you for being a member']");
    }

    private function findRoot7(): \DOMNodeList
    {
        return $this->http->XPath->query("//text()[normalize-space()='SEE BENEFITS']/ancestor::tr[1]/preceding-sibling::tr[1][contains(normalize-space(), '|')]");
    }

    private function findRoot8(): \DOMNodeList
    {
        return $this->http->XPath->query("//text()[normalize-space()='View activity']/ancestor::table[1][contains(normalize-space(), 'as of')]");
    }

    private function isMembership(?string $text = ''): bool
    {
        // examples: it-62919390.eml, it-62938723.eml, it-903650402.eml

        $phrases = array_merge($this->membershipPhrases, $this->otcPhrases);

        if ($this->http->XPath->query("//node()[{$this->contains($phrases)}]")->length > 0) {
            $this->logger->debug(__FUNCTION__ . '()');

            return true;
        }

        if (empty($text)) {
            return false;
        }

        $text = preg_replace('/\s+/', ' ', $text);

        foreach ($phrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $this->logger->debug(__FUNCTION__ . '()');

                return true;
            }
        }

        return false;
    }

    private function parseOTC(Email $email, string $textPlain): bool
    {
        // examples: it-62919390.eml

        $otcPattern = "/{$this->opt($this->otcPhrases)}[:：\s]*(\d+)(?:\s*[,.;!]|\s|$)/";
        $code = $this->http->FindSingleNode("descendant::text()[{$this->contains($this->otcPhrases)}][1]", null, true, $otcPattern);

        if ($code === null && preg_match($otcPattern, preg_replace('/\s+/', ' ', $textPlain), $m)) {
            $code = $m[1];
        }

        if ($code !== null) {
            $this->logger->debug(__FUNCTION__ . '()');
            $otс = $email->add()->oneTimeCode();
            $otс->setCode($code);

            return true;
        }

        return false;
    }

    private function parseYourNumber(string $textPlain, ?string &$number = null): bool
    {
        $phrases = [
            'You recently requested that we send the member number(s) associated with your Marriott Bonvoy(TM) account(s). Your member number(s) are',
            'Nuestros registros indican que olvidó los números de socio asociados con su(s) cuenta(s) de Rewards y/o SPG. Sus números de socio son', // es
            '내부 확인 결과 고객님께서 리워즈 또는 SPG 계정과 연결된 회원번호를 분실하신 것으로 나타납니다. 고객님의 회원번호는', // ko
        ];

        $numberPattern = "/{$this->opt($phrases)}[:：\s]*(\d+)(?:\s*[,.;!]|\s|입니다|$)/"; // + Langs: ko
        $number = $this->http->FindSingleNode("descendant::text()[{$this->contains($phrases)}][1]", null, true, $numberPattern);

        if ($number === null && preg_match($numberPattern, preg_replace('/\s+/', ' ', $textPlain), $m)) {
            $number = $m[1];
        }

        $result = $number === null ? false : true;

        if ($result) {
            $this->logger->debug(__FUNCTION__ . '()');
        }

        return $result;
    }

    private function parseAccessCreated(string $textPlain, ?string &$name = null): bool
    {
        $phrases = [
            'you have successfully created online access',
            'você criou com sucesso o acesso on-line', // pt
            'ha creado correctamente el acceso por Internet', // es
            '様のリワード アカウントへのオンライン アクセスが設定されました', // ja
        ];

        $namePattern = "/(?:^|\n[ ]*|[,.;!]\s*)({$this->patterns['travellerName']})[,\s]*{$this->opt($phrases)}/u";
        $name = $this->http->FindSingleNode("descendant::text()[{$this->contains($phrases)}][1]", null, true, $namePattern);

        if ($name === null && preg_match($namePattern, preg_replace('/\s+/', ' ', $textPlain), $m)) {
            $name = $m[1];
        }

        $result = $name === null ? false : true;

        if ($result) {
            $this->logger->debug(__FUNCTION__ . '()');
        }

        return $result;
    }

    private function contains($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'contains(normalize-space(' . $node . '),' . $s . ')';
        }, $field)) . ')';
    }

    private function opt($field): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return '';
        }

        return '(?:' . implode('|', array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeDate($date, $dateInverted = null)
    {
        $this->logger->debug($date);

        if ($dateInverted !== null) {
            $enDatesInverted = true;
        } else {
            $enDatesInverted = false;
        }

        if (preg_match('/\b\d{1,2}[\/\.](\d{1,2})[\/\.]\d{4}\b/', $date, $m) && (int) $m[1] > 12) {
            // 05/16/2019
            $enDatesInverted = false;
        }

        $in = [
            // 19/12/2018
            '#^(\d+)[\/\.](\d+)[\/\.](\d+)$#u',
        ];
        $out[0] = $enDatesInverted ? '$3-$2-$1' : '$3-$1-$2';
        $str = strtotime($this->dateStringToEnglish(preg_replace($in, $out, $date)));

        return $str;
    }

    private function dateStringToEnglish($date)
    {
        if (preg_match('#[[:alpha:]]+#iu', $date, $m)) {
            $monthNameOriginal = $m[0];

            if ($translatedMonthName = MonthTranslate::translate($monthNameOriginal, $this->lang)) {
                return preg_replace("#$monthNameOriginal#i", $translatedMonthName, $date);
            }
        }

        return $date;
    }
}
