<?php

namespace AwardWallet\Engine\onriva\Email;

use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class BookingConfirmationFor extends \TAccountChecker
{
    public $mailFiles = "onriva/it-892421400.eml, onriva/it-895060550.eml, onriva/it-895455359.eml, onriva/it-895818695.eml, onriva/it-895875247.eml, onriva/it-896307131.eml";
    public $subjects = [
        'Booking Confirmation for your trip to',
    ];

    public $lang = 'en';
    public $lastDay;

    public static $dictionary = [
        "en" => [
            'Your Itinerary - Trip to' => ['Your Itinerary - Trip to', 'Your Itinerary - Do Not Ask Me To'],
        ],
    ];

    public function detectEmailByHeaders(array $headers)
    {
        if (isset($headers['from']) && stripos($headers['from'], '@onriva.com') !== false) {
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
        if ($this->http->XPath->query("//text()[{$this->contains('www.onriva.com')}]")->length === 0
            && $this->http->XPath->query("//text()[{$this->contains('Thank you for booking your trip with Onriva')}]")->length === 0) {
            return false;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Itinerary - Trip to'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Onriva Booking Reference:'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Flight Fare rules'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Itinerary - Trip to'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Pick-Up'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Estimated Rent Total'))}]")->length > 0) {
            return true;
        }

        if ($this->http->XPath->query("//text()[{$this->contains($this->t('Your Itinerary - Trip to'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Check-In'))}]")->length > 0
            && $this->http->XPath->query("//text()[{$this->contains($this->t('Hotel Policies'))}]")->length > 0) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return preg_match('/[@.]onriva\.com$/', $from) > 0;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $otaConf = array_unique(array_filter($this->http->FindNodes("//text()[starts-with(normalize-space(), 'Onriva Booking Reference:')]/following::text()[normalize-space()][1]", null, "/^([A-Z\d]{6})$/")));

        foreach ($otaConf as $ota) {
            $email->ota()
                ->confirmation($ota);
        }

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Flight Fare rules')]")->length > 0) {
            $this->ParseFlight($email);
        }

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Pick-Up')]")->length > 0) {
            $this->ParseCar($email);
        }

        if ($this->http->XPath->query("//text()[contains(normalize-space(), 'Check-In')]")->length > 0) {
            $this->ParseHotel($email);
        }

        $class = explode('\\', __CLASS__);
        $email->setType(end($class) . ucfirst($this->lang));

        return $email;
    }

    public function ParseFlight(Email $email)
    {
        $flightNodes = $this->http->XPath->query("//text()[starts-with(normalize-space(), 'Onriva Booking Reference:')]/ancestor::table[2]");

        foreach ($flightNodes as $flightRoot) {
            $f = $email->add()->flight();

            $confDesc = $this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Booked:')]/preceding::text()[contains(normalize-space(), 'booking reference')][1]", $flightRoot, true, "/^(.+)\:/");
            $conf = $this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Booked:')]/preceding::text()[contains(normalize-space(), 'booking reference')][1]/following::text()[normalize-space()][1]", $flightRoot);
            $traveller = $this->http->FindSingleNode("./descendant::text()[normalize-space()='Name']/ancestor::tr[1]/following-sibling::tr[1]/descendant::td[1]", $flightRoot, true, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/");

            if (!empty($conf) && !empty($confDesc)) {
                $f->general()
                    ->confirmation($conf, $confDesc);
            } elseif (empty($conf)) {
                $conf = $this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Onriva Booking Reference:')]/following::text()[normalize-space()][1]", $flightRoot, true, "/^([A-Z\d]{6})$/");

                if (!empty($conf)) {
                    $f->general()
                        ->confirmation($conf);
                }
            }

            $f->general()
                ->traveller($traveller);

            $date = $this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Status:')]/following::text()[starts-with(normalize-space(), 'Booked:')][1]", $flightRoot, true, "/{$this->opt($this->t('Booked:'))}\s*(.+\d{4})$/");

            if (!empty($date)) {
                $f->general()
                    ->date(strtotime($date));
            }

            $status = $this->http->FindSingleNode("./descendant::text()[starts-with(normalize-space(), 'Status:')]", $flightRoot, true, "/{$this->opt($this->t('Status:'))}\s*(\w+)$/");

            if (!empty($status)) {
                $f->general()
                    ->status($status);
            }

            $ticketsInfo = implode("\n", $this->http->FindNodes("./descendant::text()[normalize-space()='Ticket #'][1]/ancestor::tr[1]/following-sibling::tr[1]/descendant::td[1]/following-sibling::td", $flightRoot));

            if (empty($ticketsInfo)) {
                $ticketsInfo = implode("\n", $this->http->FindNodes("./descendant::text()[normalize-space()='Loyalty Program'][1]/ancestor::tr[1]/following-sibling::tr[1]/descendant::td[1]/following-sibling::td", $flightRoot));
            }

            if (preg_match("/^(?<ticket>[\d\/]{12,})$/mu", $ticketsInfo, $m)) {
                $f->addTicketNumber($m['ticket'], false, $traveller);
            }

            if (preg_match("/^(?<account>[A-Z]{2}\s[A-z\d]{7,})$/mu", $ticketsInfo, $m)) {
                $f->addAccountNumber($m['account'], false, $traveller);
            }

            $price = $this->http->FindSingleNode("./descendant::text()[normalize-space()='Flight Total']/ancestor::tr[1]/descendant::td[2]", $flightRoot);

            if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>[\,\d\,\.\']+)/", $price, $m)) {
                $f->price()
                    ->currency($m['currency'])
                    ->total(PriceHelper::parse($m['total'], $m['currency']));

                $cost = $this->http->FindSingleNode("./descendant::text()[normalize-space()='Base Fare']/ancestor::tr[1]/descendant::td[2]", $flightRoot, true, "/\D{1,3}([\d\.\,\']+)/");

                if ($cost !== null) {
                    $f->price()
                        ->cost(PriceHelper::parse($cost, $m['currency']));
                }

                $tax = $this->http->FindSingleNode("./descendant::text()[normalize-space()='Taxes and fees']/ancestor::tr[1]/descendant::td[2]", $flightRoot, true, "/\D{1,3}([\d\.\,\']+)/");

                if ($tax !== null) {
                    $f->price()
                        ->tax(PriceHelper::parse($tax, $m['currency']));
                }
            }

            //$nodes = $this->http->XPath->query("//text()[starts-with(normalize-space(), 'Onriva Booking Reference:')]/following::text()[contains(normalize-space(), ') -')]/ancestor::tr[1]/following-sibling::tr");
            $nodes = $this->http->XPath->query("./descendant::text()[contains(translate(normalize-space(.),'0123456789','dddddddddd--'),'dd:dd')]/ancestor::tr[3]", $flightRoot);
            $nodesSeats = $this->http->XPath->query("./descendant::text()[starts-with(normalize-space(), 'Seats')]/ancestor::tr[1]", $flightRoot);

            if ($nodesSeats->length > $nodes->length) {
                $nodes = $nodesSeats;
            }

            foreach ($nodes as $root) {
                $s = $f->addSegment();

                $date = $this->http->FindSingleNode("./preceding::text()[normalize-space()][1]", $root, true, "/^(.+\d{4})$/");

                if (!empty($date)) {
                    $this->lastDay = $date;
                }

                $segText = implode("\n", $this->http->FindNodes("./descendant::text()[normalize-space()]", $root));

                if (preg_match("/^.+[\n\s](?<aName>[A-Z\d]{2})\s+(?<fNumber>\d{2,4})[\n\s](?<cabin>.+)\n(?:Operated by\n(?<operator>.+))?/", $segText, $m)) {
                    $s->airline()
                        ->name($m['aName'])
                        ->number($m['fNumber']);

                    if (isset($m['operator']) && !empty($m['operator'])) {
                        $s->airline()
                            ->operator($m['operator']);
                    }

                    $s->extra()
                        ->cabin($m['cabin']);
                }

                if (preg_match("/(?<depCode>[A-Z]{3})[\n\s](?<depTime>[\d\:]+\s*[AP]M)\n*(?:(?<nextDayD>[+]\dd\n))?(?:Terminal\s*(?<depT>\S+)\n)?(?<cabin>\d+(?:h|m).*)\n(?<arrCode>[A-Z]{3})[\n\s]*(?<arrTime>[\d\:]+\s*[AP]M)\n*(?:(?<nextDayA>[+]\dd\n))?(?:Terminal\s*(?<arrT>\S+)\n*)?(?:Seats\n(?<seat>\d+[A-Z]))?$/", $segText, $m)) {
                    $s->departure()
                        ->code($m['depCode'])
                        ->terminal($m['depT'], true, true);

                    if (isset($m['nextDayD']) && !empty($m['nextDayD'])) {
                        $s->departure()
                            ->date(strtotime('+1 day', $this->normalizeDate($this->lastDay . ', ' . $m['depTime'])));
                    } else {
                        $s->departure()
                            ->date($this->normalizeDate($this->lastDay . ', ' . $m['depTime']));
                    }

                    $s->arrival()
                        ->code($m['arrCode'])
                        ->terminal($m['arrT'], true, true);

                    if (isset($m['nextDayA']) && !empty($m['nextDayA'])) {
                        $s->arrival()
                            ->date(strtotime('+1 day', $this->normalizeDate($this->lastDay . ', ' . $m['arrTime'])));
                    } else {
                        $s->arrival()
                            ->date($this->normalizeDate($this->lastDay . ', ' . $m['arrTime']));
                    }

                    if (!empty($m['seat'])) {
                        $s->addSeat($m['seat'], true, true, $traveller);
                    }
                }
            }
        }
    }

    public function ParseCar(Email $email)
    {
        $nodes = $this->http->XPath->query("//text()[normalize-space()='Pick-Up']");

        foreach ($nodes as $root) {
            $r = $email->add()->rental();

            $date = $this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(),'Booked:')][1]", $root, true, "/{$this->opt($this->t('Booked:'))}\s*(.+\d{4})$/");

            if (!empty($date)) {
                $r->general()
                    ->date(strtotime($date));
            }

            $confDesc = $this->http->FindSingleNode("./preceding::text()[contains(normalize-space(),'Confirmation #')][1]", $root, true, "/^(.+)\:/");
            $conf = $this->http->FindSingleNode("./preceding::text()[contains(normalize-space(),'Confirmation #')][1]/following::text()[normalize-space()][1]", $root);

            if (!empty($conf) && !empty($confDesc)) {
                $r->general()
                    ->confirmation($conf, $confDesc)
                    ->traveller($this->http->FindSingleNode("./following::text()[normalize-space()='Driver'][1]/ancestor::tr[1]/following-sibling::tr[1]/descendant::text()[normalize-space()][1]", $root, true, "/^([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])$/"));
            }

            if ($this->http->XPath->query("//text()[{$this->contains('Car Rental Loyalty Program')}]")->length > 0) {
                $accountText = $this->http->FindSingleNode("./following::text()[normalize-space()='Driver'][1]/ancestor::tr[1]/following-sibling::tr[1]/descendant::text()[normalize-space()][last()]", $root);

                if (preg_match("/^(?<desc>.+)[#]\s*(?<acc>[A-Z\d]+)$/", $accountText, $m)) {
                    $r->addAccountNumber($m['acc'], false, $r->getTravellers()[0][0], $m['desc']);
                }
            }

            $r->setCarModel($this->http->FindSingleNode("./preceding::text()[contains(normalize-space(),'or similar')][1]", $root));
            $r->setCarType($this->http->FindSingleNode("./preceding::text()[contains(normalize-space(),'or similar')][1]/following::text()[normalize-space()][1]/ancestor::tr[1]", $root, true, "/Passengers?\,(.+)/"));
            $r->setCompany($this->http->FindSingleNode("./preceding::text()[contains(normalize-space(),'-')][1]/preceding::text()[normalize-space()][1]", $root));

            $dateStart = $this->http->FindSingleNode("./ancestor::tr[1]/descendant::td[normalize-space()][1]", $root, true, "/{$this->opt($this->t('Pick-Up'))}\s*(.+\d{4}.+)/");
            $r->pickup()
                ->date($this->normalizeDate($dateStart))
                ->location($this->http->FindSingleNode("./ancestor::tr[1]/following-sibling::tr[1]/descendant::td[1]", $root))
                ->phone($this->http->FindSingleNode("./ancestor::tr[1]/following-sibling::tr[2]/descendant::td[1]", $root, true, "/{$this->opt($this->t('Phone:'))}\s*(.+)/"), true, true)
                ->openingHours($this->http->FindSingleNode("./ancestor::tr[1]/following-sibling::tr[3]/descendant::td[1]", $root, true, "/{$this->opt($this->t('Work hours:'))}\s*(.+)/"), true, true);

            $dateEnd = $this->http->FindSingleNode("./ancestor::tr[1]/descendant::td[normalize-space()][last()]", $root, true, "/{$this->opt($this->t('Drop-Off'))}\s*(.+\d{4}.+)/");
            $r->dropoff()
                ->date($this->normalizeDate($dateEnd))
                ->location($this->http->FindSingleNode("./ancestor::tr[1]/following-sibling::tr[1]/descendant::td[last()]", $root))
                ->phone($this->http->FindSingleNode("./ancestor::tr[1]/following-sibling::tr[2]/descendant::td[last()]", $root, true, "/{$this->opt($this->t('Phone:'))}\s*(.+)/"), true, true)
                ->openingHours($this->http->FindSingleNode("./ancestor::tr[1]/following-sibling::tr[3]/descendant::td[last()]", $root, true, "/{$this->opt($this->t('Work hours:'))}\s*(.+)/"), true, true);

            $price = $this->http->FindSingleNode("./following::text()[normalize-space()='Estimated Rent Total'][1]/ancestor::tr[1]", $root, true, "/{$this->opt($this->t('Estimated Rent Total'))}\s*(.+)/");

            if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>[\,\d\,\.\']+)/", $price, $m)) {
                $r->price()
                    ->currency($m['currency'])
                    ->total(PriceHelper::parse($m['total'], $m['currency']));
            }
        }
    }

    public function ParseHotel(Email $email)
    {
        $nodes = $this->http->XPath->query("//text()[normalize-space()='Check-In']");

        foreach ($nodes as $root) {
            $h = $email->add()->hotel();
            $h->general()
                ->travellers($this->http->FindNodes("./following::text()[normalize-space()='Guest'][1]/ancestor::tr[1]/following-sibling::tr/descendant::text()[normalize-space()][1]", $root, "/^([[:alpha:]][-.\/\'’[:alpha:] ]*[[:alpha:]])$/"))
                ->confirmation($this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(), 'Reservation #')][1]/following::text()[normalize-space()][1]", $root, true, "/^([A-Z\d]{8,})$/"))
                ->cancellation($this->http->FindSingleNode("./following::text()[starts-with(normalize-space(), 'CANCELLATION POLICY:')][1]/ancestor::tr[1]", $root, true, "/{$this->opt($this->t('CANCELLATION POLICY:'))}\s*(.+)/s"));

            $date = $this->http->FindSingleNode("./preceding::text()[starts-with(normalize-space(),'Booked:')][1]", $root, true, "/{$this->opt($this->t('Booked:'))}\s*(.+\d{4})$/");

            if (!empty($date)) {
                $h->general()
                    ->date(strtotime($date));
            }

            $hotelInfo = implode("\n", $this->http->FindNodes("./ancestor::table[1]/preceding::table[1]/descendant::text()[normalize-space()][not(contains(normalize-space(), 'Error'))]", $root));

            if (preg_match("/^(?<hName>.+)\n.+\s+\-\s+.+\d{4}\n(?<hAddress>.+)(?:\nPhone Number[\s\n](?<hPhone>([\d\-]+)))?$/", $hotelInfo, $m)) {
                $h->hotel()
                    ->name($m['hName'])
                    ->address($m['hAddress'])
                    ->phone($m['hPhone'], true, true);
            }

            if ($this->http->XPath->query("//text()[{$this->contains('Hotel Loyalty Program')}]")->length > 0) {
                $accountText = $this->http->FindSingleNode("./following::text()[normalize-space()='Guest'][1]/ancestor::tr[1]/following-sibling::tr[1]/descendant::text()[normalize-space()][last()]", $root);

                if (preg_match("/^(?<desc>.+)[#]\s*(?<acc>[A-Z\d]+)$/", $accountText, $m)) {
                    $h->addAccountNumber($m['acc'], false, $h->getTravellers()[0][0], $m['desc']);
                }
            }

            $h->booked()
                ->checkIn($this->normalizeDate($this->http->FindSingleNode("./ancestor::table[1]/descendant::td[1]", $root, true, "/{$this->opt($this->t('Check-In'))}\s*(.+)/")))
                ->checkOut($this->normalizeDate($this->http->FindSingleNode("./ancestor::table[1]/descendant::td[last()]", $root, true, "/{$this->opt($this->t('Check-Out'))}\s*(.+)/")));

            $guests = [];

            $roomsInfo = $this->http->FindNodes("./ancestor::table[1]/ancestor::tr[1]/preceding::tr[1]/descendant::div", $root);

            foreach ($roomsInfo as $roomInfo) {
                if (preg_match("/^(?<roomType>.+)\,\s+(?<guest>\d+)\s*guests?$/iu", $roomInfo, $m)) {
                    $h->addRoom()->setType($m['roomType']);
                    $guests[] = $m['guest'];
                }
            }

            $h->setGuestCount(array_sum($guests));

            $this->detectDeadLine($h);

            $price = $this->http->FindSingleNode("./following::text()[normalize-space()='Total Stay'][1]/ancestor::tr[1]/descendant::td[2]", $root);

            if (preg_match("/^(?<currency>\D{1,3})\s*(?<total>[\,\d\,\.\']+)/", $price, $m)) {
                $h->price()
                    ->currency($m['currency'])
                    ->total(PriceHelper::parse($m['total'], $m['currency']));

                $cost = $this->http->FindSingleNode("./following::text()[normalize-space()='Base Rate'][1]/ancestor::tr[1]/descendant::td[2]", $root, true, "/\D{1,3}([\d\.\,\']+)/");

                if ($cost !== null) {
                    $h->price()
                        ->cost(PriceHelper::parse($cost, $m['currency']));
                }

                $tax = $this->http->FindSingleNode("./following::text()[normalize-space()='Tax included'][1]/ancestor::tr[1]/descendant::td[2]", $root, true, "/\D{1,3}([\d\.\,\']+)/");

                if ($tax !== null) {
                    $h->price()
                        ->tax(PriceHelper::parse($tax, $m['currency']));
                }
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

    private function t($word)
    {
        if (!isset(self::$dictionary[$this->lang]) || !isset(self::$dictionary[$this->lang][$word])) {
            return $word;
        }

        return self::$dictionary[$this->lang][$word];
    }

    private function opt($field)
    {
        $field = (array) $field;

        return '(?:' . implode("|", array_map(function ($s) {
            return str_replace(' ', '\s+', preg_quote($s, '/'));
        }, $field)) . ')';
    }

    private function normalizeDate($date)
    {
        $in = [
            // 11 Apr, 2025, 05:40 AM
            "/^(\d+)\s+(\w+)\,\s+(\d{4})\,\s+(\d+\:\d+\s*[AP]M)$/iu",
            // Apr 23, 2025 3:00 PM - 1:00 AM
            "/^(\w+)\s+(\d+)\,\s+(\d{4})\s+(\d+\:\d+\s*[AP]M)(?:[\s\-]+\d+\:\d+\s*[AP]M)?$/",
        ];
        $out = [
            "$1 $2 $3, $4",
            "$2 $1 $3, $4",
        ];
        $date = preg_replace($in, $out, $date);

        if (preg_match("#\d+\s+([^\d\s]+)\s+\d{4}#", $date, $m)) {
            if ($en = \AwardWallet\Engine\MonthTranslate::translate($m[1], $this->lang)) {
                $date = str_replace($m[1], $en, $date);
            }
        }

        return strtotime($date);
    }

    private function detectDeadLine(Hotel $h)
    {
        if (empty($cancellationText = $h->getCancellation())) {
            return;
        }

        if (preg_match("/Cancellations made after\s+(\w+)\s+(\d+)\,\s*(\d{4})\s+([\d\:]+\s*[AP]M)/u", $cancellationText, $m)
            || preg_match("/No cancellation penalty until\s*(\w+)\s*(\d+)\,\s+(\d{4})\s+([\d\:]+\s*[AP]M)/u", $cancellationText, $m)) {
            $h->booked()
                ->deadline(strtotime($m[2] . ' ' . $m[1] . ' ' . $m[3] . ', ' . $m[4]));
        }
    }
}
