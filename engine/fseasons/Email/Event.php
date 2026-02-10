<?php

namespace AwardWallet\Engine\fseasons\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Engine\MonthTranslate;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Event extends \TAccountChecker
{
    public $mailFiles = "fseasons/it-100822772.eml, fseasons/it-101502400.eml, fseasons/it-101905896.eml, fseasons/it-113231769.eml, fseasons/it-139121219.eml, fseasons/it-189990835.eml, fseasons/it-377937715.eml, fseasons/it-387467393.eml, fseasons/it-512956201-fr.eml, fseasons/it-65294517.eml, fseasons/it-67625849.eml, fseasons/it-904733066.eml, fseasons/it-905959098.eml, fseasons/it-906779166.eml, fseasons/it-907743491.eml, fseasons/it-908504269.eml, fseasons/it-909815166.eml, fseasons/it-910945511.eml, fseasons/it-910947663.eml, fseasons/it-910970980.eml, fseasons/it-911788918.eml, fseasons/it-912002350.eml, fseasons/it-912066868.eml, fseasons/it-912177167.eml, fseasons/it-913304556.eml, fseasons/it-913991786.eml, fseasons/it-921047803.eml, fseasons/it-926530738.eml, fseasons/it-926726797.eml, fseasons/it-929238372.eml";

    public $detectSubject = [
        'Reservation Confirmation',
        'Booking Confirmation#',
        'Booking Confirmation #',
        'Booking Reminder for Confirmation#',
        'Itinerary for Reservation',
        'Welcome to ',
    ];

    public $lang = 'en';

    public static $dictionary = [
        'fr' => [
            'dear'          => ['Cher(e)'],
            'confNumber'    => ['votre réservation est'],
            'service'       => ['Soin:', 'Soin :'],
            'beforeAddress' => ['sommes ravis de vous accueillir prochainement au'],
            'afterAddress'  => ['. Si vous avez besoin'],
            'date'          => ['Date:', 'Date :'],
            'time'          => ['Heure:', 'Heure :'],
            'minutes'       => ['minutes', 'minute', 'min', 'm'],
            'beforePhone'   => ['veuillez nous contacter en appelant le'],
            'price'         => ['Tarif:', 'Tarif :'],
        ],
        'en' => [
            'Spa'               => ['Spa', 'SPA', 'spa'],
            'dear'              => ['Dear', 'Greetings', 'Aloha', 'Hi', 'Confirmation Email for', 'Itinerary for'],
            'guest'             => ['Guest Name', 'Name of guest:'],
            'confNumber'        => ['Confirmation Number:', 'Confirmation Number :', 'CONFIRMATION NUMBER:', 'spa appointment is', 'spa booking is', 'Your confirmation number is:'],
            'confNumberSubject' => ['Booking Confirmation', 'Booking Reminder for Confirmation'],
            'service'           => ['Service:', 'Service :', 'Service/', 'Service /', 'Service', 'Experience:', 'Experience :', 'Treatment:', 'Treatment :', 'TREATMENT:', 'Wellness experience:', 'Treatment Programme', 'Treatment details:', 'Service(s):', 'Type of Service'],
            'beforeSpaName'     => ['Thank you for choosing the', 'Thank you for choosing', 'during your time with', 'confirm your appointment at', 'your visit with us at', 'Thank you for selecting'],
            'afterSpaName'      => '. We are delighted to confirm',
            'at'                => ['at', 'in'],
            'beforeAddress'     => 'We are located on the',
            'afterAddress'      => 'and are available via',
            'beforePhone'       => ['please contact us at', 'call us at'],
            'date'              => ['Date:', 'Date :', 'Date/', 'Date /', 'Date', 'Treatment Date:', 'Date of Treatment:', 'Appointment Date:', 'Appointment Date', 'Services to be provided on:', 'RESERVATION DATE:'],
            'time'              => ['Time:', 'Time :', 'Time/', 'Time /', 'Time', 'Hour:', 'Start Time:', 'Treatment Time:', 'Time of Treatment:', 'Time of Service', 'Commencing', 'RESERVATION TIME:'],
            'hours'             => ['hours', 'hour', 'h'],
            'minutes'           => ['minutes', 'minute', 'mins', 'min', 'm'],
            'price'             => ['Price:', 'Price :', 'Cost:', 'Cost :', 'Cost/', 'Cost /', 'Cost', 'Price', 'Service Price:'],
            'afterPrice'        => ['Price includes', 'includes'],
        ],
        'es' => [
            'dear'         => ['Hola'],
            'confNumber'   => ['El número de confirmación para su próxima reserva de spa es'],
            'service'      => ['Servicio:'],
            'date'         => ['Fecha:'],
            'time'         => ['Hora:'],
            'minutes'      => ['minutes', 'minute', 'min', 'm'],
            'price'        => ['Costo:'],
        ],
    ];

    private $providerCode = '';

    private static $detectProviders = [
        'aman'      => [
            'from' => [
                '@amanspa.com',
                '@aman.com',
            ],
            'uniqueProviderName' => ['Aman'],
            'uniqueSubject'      => [],
            'body'               => [
                'Aman Spa New York',
                'Aman Group',
            ],
        ],
        'aplus'     => [
            'from' => [
                '@banyantree.com',
                '@sofitel.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                'Banyan Tree Mayakoba',
                'Sofitel Spa St James',
            ],
        ],
        'auberge'   => [
            'from' => [
                '@aubergeresorts.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                '//img[contains(@src, "Auberge")]',
                'Auberge',
                '2702 Main St | Gardiner, New York | 12525', // Thistle Spa
                '37 Beach Ave | Kennebunk Maine | 04043',
                '118 Woodbury Road | Washington, Connecticut | 06793', // The Retreat at Mayflower Inn
            ],
        ],
        'belmond'   => [
            'from' => [
                '@belmond.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                'Belmond',
            ],
        ],
        'disneyresort' => [
            'from' => [
                '@disneyworld.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                'The Grand Floridian Spa',
            ],
        ],
        'fairmont'  => [
            'from' => [
                '@fairmont.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                'Fairmont',
            ],
        ],
        'fontain'   => [
            'from' => [
                '@fblasvegas.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                'Lapis Spa & Wellness',
            ],
        ],
        'fseasons'  => [
            'from' => [
                '@spa.fourseasons.com',
                '@fourseasons.com',
            ],
            'uniqueProviderName' => ['Four Seasons'],
            'uniqueSubject'      => [],
            'body'               => [
                '@fourseasons.com',
                'Four Seasons',
                '9500 Wilshire Blvd. | Beverly Hills, California | 90212',
            ],
        ],
        'goldpassport' => [
            'from' => [
                '@hyatt.com',
            ],
            'uniqueProviderName' => ['Hyatt'],
            'uniqueSubject'      => [],
            'body'               => [
                'consumeraffairs@hyatt.com',
                'Hyatt Corporation',
            ],
        ],
        'hhonors'   => [
            'from' => [
                '@waldorfastoria-spa.com',
                '@conradhotels.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [
                'Waldorf Astoria Spa. Booking Confirmation#',
            ],
            'body' => [
                '@waldorfastoria.com',
            ],
        ],
        'jumeirah'  => [
            'from' => [
                '@jumeirah.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                '//a[contains(@href, "jumeirah.com")]',
                '@jumeirah.com',
                'Jumeirah',
            ],
        ],
        'langham'   => [
            'from' => [
                '@langhamhotels.com',
                '@chuanspa.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                '@langhamhotels.com',
                'Langham',
                'Chuan Spa Melbourne',
                'Day Spa by Chuan',
            ],
        ],
        'marriott'  => [
            'from' => [
                '@marriott.com',
                '@ritzcarlton.com',
                '@stregis.com',
                '@noreply-stregis.com',
                '@westin.com',
            ],
            'uniqueProviderName' => ['Marriott'],
            'uniqueSubject'      => [],
            'body'               => [
                'Marriott Global Privacy Statement',
                'The Spa at Newport Marriott',
                'The Ritz-Carlton',
                'W Reserva Conchal- Costa Rica | Cabo Velas, Santa Cruz',
                'Kawasan Pariwisata | Nusa Dua Bali Lot S6 Po BOX 44, | 10000', // Iridium Spa
                'Spa at the Mission San Juan Capistrano',
                'St. Regis Atlanta Spa',
                'The St. Regis Mumbai',
                'Heavenly Spa by Westin',
                'Spa by JW Marriott',
                'Gaylord Opryland Resort',
            ],
        ],
        'mirage'    => [
            'from' => [
                '@mgmresorts.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                '//img[contains(@src, "mgmresorts")]',
                'MGM RESORTS INTERNATIONAL',
            ],
        ],
        'rwvegas'   => [
            'from' => [
                '@rwlasvegas.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                '3000 Las Vegas Boulevard South',
            ],
        ],
        'shangrila' => [
            'from' => [
                '@shangri-la.com',
            ],
            'uniqueProviderName' => ['Shangri-La'],
            'uniqueSubject'      => [],
            'body'               => [
                '//a[contains(@href, "shangri-la.com")]',
                'Shangri-La',
            ],
        ],
        'wynnlv'    => [
            'from' => [
                '@wynnlasvegas.com',
            ],
            'uniqueProviderName' => [],
            'uniqueSubject'      => [],
            'body'               => [
                'at Wynn',
            ],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (empty($headers['from']) || empty($headers['subject'])) {
            return false;
        }

        foreach (self::$detectProviders as $code => $detect) {
            if (empty($detect['from']) && empty($detect['uniqueSubject']) && empty($detect['uniqueProviderName'])) {
                continue;
            }

            foreach ($detect['uniqueProviderName'] as $dUniqueProviderName) {
                if (preg_match("/\b{$this->opt($dUniqueProviderName)}\b/", $headers['subject'])) {
                    return true;
                }
            }

            $byFrom = false;

            foreach ($detect['from'] as $dfrom) {
                if (stripos($headers['from'], $dfrom) !== false) {
                    $byFrom = true;

                    break;
                }
            }

            if ($byFrom === false) {
                continue;
            }

            $dSubjects = empty($detect['uniqueSubject']) ? $this->detectSubject : array_merge($detect['uniqueSubject'], $this->detectSubject);

            foreach ($dSubjects as $dSubject) {
                if (stripos($headers['subject'], $dSubject) !== false) {
                    $this->providerCode = $code;

                    return true;
                }
            }
        }

        return false;
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        // detect Provider
        if (empty($this->getProviderByBody())
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Spa'))}]")->length === 0 // none provider
        ) {
            return false;
        }

        // detect Format
        return $this->assignLang();
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]spa\.fourseasons\.com$/i', $from) > 0
            || preg_match('/.*spa.*@fourseasons\.com$/i', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $this->assignLang();

        if (empty($this->lang)) {
            $this->logger->debug("Can't determine a language!");

            return $email;
        }

        if (empty($this->providerCode)) {
            $this->providerCode = $this->getProvider($parser);
        }

        if (!empty($this->providerCode)) {
            $email->setProviderCode($this->providerCode);
        }

        $this->parseEmailHtml($parser, $email);

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

    public static function getEmailProviders()
    {
        return array_keys(self::$detectProviders);
    }

    private function parseEmailHtml(PlancakeEmailParser $parser, Email $email): void
    {
        $patterns = [
            'time'          => '(?:\d{1,2}[:：]\d{2}|\d{1,2}(?:[:：]\d{2})?(?:[ ]*[AaPp](?:\.[ ]*)?[Mm]\.?))', // 4:19PM    |    2:00 p. m.    |    3pm
            'phone'         => '[+(\d][-+. \d)(]{5,}[\d)]', // +377 (93) 15 48 52    |    (+351) 21 342 09 07    |    713.680.2992
            'travellerName' => "(?:{$this->opt(['Dr', 'Miss', 'Mrs', 'Mr', 'Ms', 'Mme', 'Mr/Mrs', 'Mrs/Mr', 'Mrs./Sra.'])}[\.\s]*)?([[:alpha:]][-.\'’[:alpha:] ]*[[:alpha:]])", // Mr. Hao-Li Huang => Hao-Li Huang
        ];

        // collect confirmation info
        $confirmation = $this->http->FindSingleNode("//text()[{$this->contains($this->t('confNumber'))}]/following::text()[normalize-space()][1]", null, true, "/^[-A-Z\d]{5,}$/");

        if (!$confirmation && preg_match("/{$this->opt($this->t('confNumberSubject'))}[#\s]*([-A-Z\d]{5,})\s*(?:[,.:;!?]|$)/i", $parser->getSubject(), $m)) {
            $confirmation = $m[1];
        }

        // collect nodes with events
        $roots = $this->http->XPath->query("//text()[{$this->eq($this->t('service'))}]/ancestor::*[count(descendant::text()[normalize-space()]) = 2][1]/*[normalize-space()][1]");

        if ($roots->length === 0) {
            $roots = $this->http->XPath->query("//text()[{$this->eq($this->t('service'))}]/ancestor::*[1]");
        }

        if ($roots->length === 0) {
            $this->logger->debug('Wrong count roots!');

            return;
        }

        foreach ($roots as $rootNumber => $root) {
            $e = $email->add()->event();

            if ($confirmation) {
                $e->general()
                    ->confirmation($confirmation);
            } else {
                $e->general()
                    ->noConfirmation();
            }

            // collect traveller
            $travellerPos = $rootNumber + 1;
            $traveller = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('guest'))}])[$travellerPos]/following::text()[normalize-space()][1]",
                null, true, "/^\s*{$patterns['travellerName']}\s*$/u");

            if (empty($traveller)) {
                $travellerNodes = array_unique(array_filter($this->http->FindNodes("//text()[{$this->starts($this->t('dear'))} and not({$this->contains($this->t('from'))})][normalize-space()]",
                    null, "/^\s*{$this->opt($this->t('dear'))}\s+{$patterns['travellerName']}[\s\*]*(?:[,;:!?]|\#\d+|$)/u")));

                if (empty($travellerNodes)) {
                    $travellerNodes = array_unique(array_filter($this->http->FindNodes("(//text()[{$this->eq($this->t('dear'))}])[$travellerPos]/following::text()[normalize-space()][1][ following::text()[normalize-space()][1][{$this->eq([',', ';', ':', '!', '?'])}] ]",
                        null, "/^\s*{$patterns['travellerName']}\s*$/u")));
                }

                if (!empty($travellerNodes)) {
                    $traveller = end($travellerNodes);
                }
            }

            if ($traveller !== null) {
                $e->addTraveller($traveller);
            }

            // collect date
            $datePos = $rootNumber + 1;

            if ($this->http->XPath->query("//text()[{$this->eq($this->t('date'))}]")->length === 1) {
                $datePos = 1;
            }

            if ($roots->length < $this->http->XPath->query("//text()[{$this->eq($this->t('date'))}]")->length) {
                $datePos++;
            }

            $date = $this->normalizeDate($this->http->FindSingleNode("(//text()[{$this->eq($this->t('date'))}])[{$datePos}]/following::text()[not({$this->eq(':')})][normalize-space()][1]"));

            // collect start time
            $timePos = $rootNumber + 1;
            $time = $this->http->FindSingleNode("(//text()[{$this->eq($this->t('time'))}])[{$timePos}]/following::text()[not({$this->eq(':')})][normalize-space()][1]");
            $startDate = null;

            // collect name
            $name = $this->http->FindSingleNode("./following::text()[not({$this->eq(':')})][normalize-space()][1]", $root);

            if (preg_match("/^\s*({$patterns['time']})[\s\:]*$/", $name, $m)) {
                $time = $m[1];
                $name = $this->http->FindSingleNode("./following::text()[normalize-space()][2]", $root); //$this->http->FindSingleNode("//text()[{$this->starts($name)}][1]/following::text()[normalize-space()][1]", $root);
            }

            if (!empty($date) && !empty($time)) {
                $startDate = strtotime($time, $date);
            }

            $e->setEventType(\AwardWallet\Schema\Parser\Common\Event::TYPE_EVENT)
                ->setName($name)
                ->setStartDate($startDate);

            // collect duration for calculate endDate
            $duration = $this->http->FindSingleNode("./following::text()[{$this->starts($this->t('Duration'))}][1]/following::text()[normalize-space()][1]", $root)
                ?? $this->re("/\s{$this->opt($this->t('to'))}\s+({$patterns['time']})\b/i", $e->getName());

            if (empty($duration)) {
                $hour = $this->re("/\b(\d+)[\s\-]*{$this->opt($this->t('hours'))}\b/ui", $e->getName());
                $min = $this->re("/\b(\d+)[\s\-]*{$this->opt($this->t('minutes'))}\b/ui", $e->getName());

                if (!empty($hour)) {
                    $duration = "$hour hours";
                }

                if (!empty($min)) {
                    $duration .= " $min minutes";
                }
            }

            if (!empty($duration)) {
                $e->setEndDate(strtotime($duration, $e->getStartDate()));
            } else {
                $e->setNoEndDate(true);
            }

            // collect address
            $address = $this->http->FindSingleNode("//text()[{$this->eq($this->t('Club Address:'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*(.+?)\.\s*$/")
                ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('Phone:'))}]", null, true, "/^\s*(.{10,100})\s*{$this->opt($this->t('Phone:'))}/")
                ?? $this->http->FindSingleNode("//a[{$this->eq($this->t('PRIVACY POLICY'))}]/preceding-sibling::a[1][normalize-space()]")
                ?? $this->http->FindSingleNode("(//*[{$this->contains($this->t('beforeAddress'))} and {$this->contains($this->t('afterAddress'))}])[last()]", $root, true, "/{$this->opt($this->t('beforeAddress'))}\s*(.+?)\s*{$this->opt($this->t('afterAddress'))}/")
                ?? $this->http->FindSingleNode('(//text()[contains(translate(.,"0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", "ddddddddddllllllllllllllllllllllllll"), "ll ddddd")])[last()]', null, true, "/^\s*(.+?\b[A-Z]{2}\s+\d{5})\b/")
                ?? $this->http->FindSingleNode("(//text()[{$this->contains(['|', '/', 'Tel'])}])[not({$this->contains($this->t('For questions'))})][string-length(normalize-space()) > 1][last()]", null, true, "/^\s*(.{10,100}?)\s*{$this->opt(['|', '/', 'Tel', 'TEL', '| P:'])}\s*(?:{$patterns['phone']}|0)\b(?:\s|$)/")
                ?? $this->http->FindSingleNode("(//text()[{$this->contains(['|', 'Tel'])}])[not({$this->contains($this->t('For questions'))})][string-length(normalize-space()) > 1][last()]", null, true, "/^\s*(.{10,100}?)\s*{$this->opt('|')}\s*$/")
                ?? $this->http->FindSingleNode("(//text()[{$this->contains(['|', 'Tel'])}])[not({$this->contains($this->t('For questions'))})][string-length(normalize-space()) > 1][last()]", null, true, "/^\s*(.{10,100}?\s+\d{5})\s*$/")
                ?? $this->http->FindSingleNode("(//text()[{$this->contains(['|', 'Tel'])}])[not({$this->contains($this->t('For questions'))})][string-length(normalize-space()) > 1][last()]/ancestor::td[1][normalize-space()][not({$this->contains($this->t('Website'))})]", null, true, "/^\s*(.{10,100}?)\s*(?:{$this->opt(['|', 'Tel'])}\s*{$patterns['phone']})?$/")
                ?? $this->http->FindSingleNode("(//a[{$this->contains('google.com/maps', '@href')}])[last()]", null, true, "/^\s*(.{10,100})\s*$/");

            // it-912002350.eml
            if (preg_match("/^\s*[.,]+/", $address, $m)) {
                $address = $this->http->FindSingleNode("//text()[{$this->starts($address)}]/ancestor::td[1][normalize-space()]",
                    null, true, "/^\s*(.+?{$this->opt($address)})/");
            }

            if (stripos($address, 'T:') === 0 || stripos($address, 'Tel') === 0) {
                $address = $this->http->FindSingleNode("//text()[{$this->starts($address)}]/preceding::text()[normalize-space()][1]",
                    null, true, "/^\s*(.{10,100})\s*$/");
            }

            if (strpos($address, '@') !== false) {
                $address = null;
            }

            // collect spa name (use if only address is not enough)
            $spaName = $this->http->FindSingleNode("//text()[{$this->starts($this->t('beforeSpaName'))} and {$this->contains($this->t('afterSpaName'))}]", null, true, "/{$this->opt($this->t('beforeSpaName'))}\s*(.{5,25})\s*{$this->opt($this->t('afterSpaName'))}/")
                ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('beforeSpaName'))}]", null, true, "/{$this->opt($this->t('beforeSpaName'))}\s*(.{5,50}?)\s*[\:\.]/")
                ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('beforeSpaName'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*.{5,25}?\s+{$this->opt($this->t('at'))}\s+(.{5,35})\s*$/")
                ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('beforeSpaName'))}]/following::text()[normalize-space()][1]", null, true, "/^\s*([^\.\,\!]{5,35})\s*$/");

            if (!empty($address) && !empty($spaName) && $this->providerCode === 'aman') {
                $address .= ', ' . $spaName;
            }

            if (empty($address) && !empty($spaName)) {
                $address = $spaName;
            }

            $e->setAddress($address);

            // collect phone
            $phone = $this->http->FindSingleNode("(//*[{$this->contains($e->getAddress())}])[last()]", null, true, "/{$this->opt($e->getAddress())}\s*(?:\|\s+0\s+)?(?:\s*{$this->opt(['|', 'Tel', '/', '| P:'])})?\s*({$patterns['phone']})(?:\s|$)/")
                // it-512956201-fr.eml
                ?? $this->http->FindSingleNode("(//text()[{$this->contains($this->t('beforePhone'))}])[1]", null, true, "/{$this->opt($this->t('beforePhone'))}\s+({$patterns['phone']})/")
                ?? $this->http->FindSingleNode("//text()[{$this->contains($this->t('Phone:'))}]", null, true, "/{$this->opt($this->t('Phone:'))}\s*({$patterns['phone']})(?:\s|$)/")
                ?? $this->http->FindSingleNode("//text()[{$this->starts($this->t('For questions'))}]", null, true, "/\|\s+({$patterns['phone']})\s*$/")
                ?? $this->http->FindSingleNode("(//text()[{$this->starts(['T', 'Tel'])}])[last()]", null, true, "/^\s*{$this->opt(['T', 'Tel'])}[\s\:]+({$patterns['phone']})(?:\s|[|]|$)/");

            if (empty($phone)) {
                $phones = array_unique(array_filter($this->http->FindNodes("//a/@href", null, "/^{$this->opt($this->t('Tel:'))}(\d+)$/")));

                if (count($phones) === 1) {
                    $phone = end($phones);
                }
            }

            $e->setPhone($phone, false, true);

            $totalPrice = $this->http->FindSingleNode("./following::text()[{$this->eq($this->t('price'))}][1]/following::text()[normalize-space()][1]", $root);
            $totalPrice = $this->re("/^(.+?)[\s\(]*{$this->opt($this->t('afterPrice'))}/i", $totalPrice) ?? $totalPrice;

            if (preg_match("/^\s*(?<currency>[^\d\s]{1,3})?\s*(?<amount>[\d\.\,\']+)\s*(?:\s*\(.+?\)\s*)?$/u", $totalPrice, $m)
                || preg_match("/^\s*(?<amount>[\d\.\,\']+)\s*(?<currency>[^\d\s]{1,3})\s*$/u", $totalPrice, $m)
            ) {
                if (empty($m['currency'])) {
                    $m['currency'] = null;
                }

                $currency = $this->normalizeCurrency($m['currency']);

                $e->price()
                    ->total(PriceHelper::parse($m['amount'], $currency))
                    ->currency($currency, false, true);
            }
        }
    }

    private function getProviderByBody()
    {
        foreach (self::$detectProviders as $code => $detect) {
            if (empty($detect['body'])) {
                continue;
            }

            foreach ($detect['body'] as $search) {
                if ((stripos($search, '//') === 0 && $this->http->XPath->query($search)->length > 0)
                    || (stripos($search, '//') === false && strpos($this->http->Response['body'], $search) !== false)
                ) {
                    return $code;
                }
            }
        }

        return null;
    }

    private function getProvider(PlancakeEmailParser $parser)
    {
        $this->detectEmailByHeaders(['from' => $parser->getCleanFrom(), 'subject' => $parser->getSubject()]);

        if (!empty($this->providerCode)) {
            return $this->providerCode;
        }

        return $this->getProviderByBody();
    }

    private function assignLang(): bool
    {
        if (!isset(self::$dictionary, $this->lang)) {
            return false;
        }

        foreach (self::$dictionary as $lang => $phrases) {
            if (!is_string($lang) || empty($phrases['service']) || empty($phrases['date']) || empty($phrases['time'])) {
                continue;
            }

            if ($this->http->XPath->query("//text()[{$this->eq($phrases['service'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->eq($phrases['date'])}]")->length > 0
                && $this->http->XPath->query("//text()[{$this->eq($phrases['time'])}]")->length > 0
            ) {
                $this->lang = $lang;

                return true;
            }
        }

        return false;
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

    private function starts($field, string $node = ''): string
    {
        $field = (array) $field;

        if (count($field) === 0) {
            return 'false()';
        }

        return '(' . implode(' or ', array_map(function ($s) use ($node) {
            $s = strpos($s, '"') === false ? '"' . $s . '"' : 'concat("' . str_replace('"', '",\'"\',"', $s) . '")';

            return 'starts-with(normalize-space(' . $node . '),' . $s . ')';
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function re($re, $str, $c = 1)
    {
        preg_match($re, $str, $m);

        if (isset($m[$c])) {
            return $m[$c];
        }

        return null;
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeDate(?string $date): ?int
    {
        // $this->logger->debug('date begin = ' . print_r( $date, true));
        if (empty($date)) {
            return null;
        }

        $in = [
            '/^[-[:alpha:]]+[,.\s]+([[:alpha:]]+)[.\s]+(\d{1,2})[,.\s]+(\d{4})$/u', // Tuesday, July 06, 2021       => 06 July 2021
            '/^[-[:alpha:]]+[,.\s]+(\d{1,2})[.\s]+([[:alpha:]]+)[.\s]+(\d{4})$/u',  // dimanche 17 septembre 2023   => 17 septembre 2023
            '/^\w+\,\s*(\d+)\s*de\s*(\w+)\s*de\s*(\d{4})$/u',                       // martes, 8 de febrero de 2022 => 8 febrero 2022
        ];
        $out = [
            '$2 $1 $3',
            '$1 $2 $3',
            '$1 $2 $3',
        ];

        $date = preg_replace($in, $out, $date);

        if (preg_match("/\d+\s+([^\d\s]+)\s+\d{4}/", $date, $m)) {
            if ($en = MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }
        // $this->logger->debug('date replace = ' . print_r( $date, true));

        if (preg_match("/\b\d{4}\b/", $date)) {
            $date = strtotime($date);
        } else {
            $date = null;
        }
        // $this->logger->debug('date end = ' . print_r( $date, true));

        return $date;
    }

    private function normalizeCurrency($s)
    {
        if (empty($s)) {
            return null;
        }

        $sym = [
            '€'          => 'EUR',
            'US dollars' => 'USD',
            '£'          => 'GBP',
            '₹'          => 'INR',
            'CA$'        => 'CAD',
            'R$'         => 'BRL',
            '$'          => '$',
            'Rp'         => 'IDR',
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
