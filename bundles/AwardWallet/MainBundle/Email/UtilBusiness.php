<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Security\Voter\UserAgentVoter;
use AwardWallet\Schema\Itineraries as Schema;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

require_once __DIR__ . '/../../../../web/trips/common.php';

class UtilBusiness
{
    public const SAVE_MESSAGE_FAIL = 'fail';
    public const SAVE_MESSAGE_SUCCESS = 'success';
    public const SAVE_MESSAGE_MISSED = 'missed';
    public const TRAVEL_ADMINS = [
        'AwardWallet' => 'taylor_n8n96x@awardwallet.com',
        'Deb Huettl' => 'dlhuettl@taylorcorp.com',
        'William Cook Barron' => 'keleighton@taylorcorp.com',
        'Patti Sjulstad' => 'pasjulstad@taylorcorp.com',
    ];

    public $echo = false;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $output;
    /**
     * @var \AwardWallet\MainBundle\Entity\Repositories\UsrRepository
     */
    protected $userRep;
    /**
     * @var ProviderRepository
     */
    protected $providerRep;
    /**
     * @var \AwardWallet\MainBundle\Entity\Repositories\UserAgentRepository
     */
    protected $userAgentRep;
    /**
     * @var \Doctrine\Persistence\ObjectRepository
     */
    protected $tripSegmentRep;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UserAgentVoter
     */
    private $userAgentVoter;
    /**
     * @var ItinerariesProcessor
     */
    private $saver;
    private StatementMatcher $statementMatcher;
    private StatementSaver $statementSaver;

    private $businessID;

    private $userData;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $emailLogger,
        UserAgentVoter $userAgentVoter,
        ItinerariesProcessor $itinerariesProcessor,
        StatementMatcher $statementMatcher,
        StatementSaver $statementSaver)
    {
        $this->em = $doctrine->getManager();
        $this->userRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->providerRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->userAgentRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $this->tripSegmentRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);

        $this->logger = $emailLogger;
        $this->userAgentVoter = $userAgentVoter;
        $this->saver = $itinerariesProcessor;
        $this->statementMatcher = $statementMatcher;
        $this->statementSaver = $statementSaver;

        $this->businessID = null;
        $this->userData = null;
    }

    /**
     * @return string - one of SAVE_MESSAGE_XXX constants
     */
    public function processBusinessMessage(ParseEmailResponse $data, EmailOptions $info, $businessID)
    {
        $SummaryLog = "";
        $addedItinerary = false;
        $this->businessID = $businessID;
        $this->userData = $data->userData;
        $result = self::SAVE_MESSAGE_FAIL;

        $parser = $info->parser;

        $from = Util::clearHeader($parser->getHeader("from"));
        $to = Util::clearHeader($parser->getHeader("to"));
        $subject = $parser->getSubject();

        if (empty($subject)) {
            $subject = $parser->getHeader("subject");
        }
        $this->log("{$from} -> {$to}: " . $subject);

        if ($info->isCycled()) {
            $this->log('Cycled email');

            return $result;
        }

        $parts = [];

        if (isset($to)) {
            $parts = $this->parseBusinessAddress($to, self::TRAVEL_ADMINS);
            $this->log('TO: without TRAVEL_ADMINS - ' . var_export($parts, true));
        }
        $user = $this->userRep->findOneByUserid($businessID);

        if (!empty($user)) {
            $token = new PostAuthenticationGuardToken($user, 'none', $user->getRoles());

            if (!empty($data->loyaltyAccount)) {
                $this->log('saving statement');

                if ($data->loyaltyAccount->providerCode == 'aa') {
                    $match = $this->statementMatcher->matchCustomAa(new Owner($user), $data->loyaltyAccount);
                } else {
                    $match = $this->statementMatcher->match(new Owner($user), $this->providerRep->findOneByCode($data->loyaltyAccount->providerCode), $data->loyaltyAccount);
                }

                if ($match->acc) {
                    $this->log(sprintf('found acc %d (%s)', $match->acc->getId(), $match->acc->getLogin()));
                    $emailDate = ($date = $data->metadata->receivedDateTime) ? new \DateTime($date) : null;
                    $saved = $this->statementSaver->save($match->acc, $data->loyaltyAccount, $emailDate);
                    $this->log($saved ? 'updated account' : 'update failed');
                } else {
                    $this->log($match->cnt > 0 ? 'multiple accounts matched' : 'account not found');
                }
            }

            if (!empty($data->itineraries)) {
                $this->log("saving itineraries");
                $cancelled = false;
                $toSave = [];

                foreach ($data->itineraries as $itinerary) {
                    if ($itinerary->cancelled) {
                        $cancelled = true;
                    } else {
                        $toSave[] = $itinerary;
                    }
                }

                if (count($toSave) > 0) {
                    $this->log("valid itineraries");
                    $pax = $this->getTravellers($toSave);
                    $Emails4saved = [];
                    $this->log("travellers: " . var_export($pax, true));

                    foreach ($pax as $p) {
                        $pp = mb_strtolower(str_replace(" ", "", $p));
                        // seek member by Name
                        $q = $this->em->getConnection()->executeQuery("select u.Email, ua.UserAgentID, ua.ClientID from UserAgent ua, Usr u
							where ua.AgentID = ? and ua.ClientID = u.UserID 
								and LOWER(replace(concat(u.FirstName,if(u.MidName is not null, u.MidName, '') ,u.LastName),' ','')) = ?
								and ua.IsApproved
							union all 
							select ua.Email, ua.UserAgentID, ua.ClientID from UserAgent ua
								where ua.AgentID = ? and LOWER(replace(concat(ua.FirstName,if(ua.MidName is not null, ua.MidName, '') ,ua.LastName),' ','')) = ?
", [$businessID, $pp, $businessID, $pp]);

                        if ($q->rowCount() === 1) {
                            $rowUA = $q->fetch(\PDO::FETCH_ASSOC);
                            $res = $this->savingForMemberBusiness($rowUA, $toSave, $user, $token, $info->messageId, $info->source, $data->providerCode);

                            if ($res) {
                                $Emails4saved[] = $rowUA['Email'];
                                $addedItinerary = true;
                                $SummaryLog .= '{itinerary added to AW by name: ' . $p . ' (email: ' . $rowUA['Email'] . ')}';
                                $this->log('itinerary added to AW by name: ' . $p . ' (email: ' . $rowUA['Email'] . ')');
                                $result = self::SAVE_MESSAGE_SUCCESS;
                            }
                        } else {
                            $SummaryLog .= '{not added to AW by name: ' . $p . '}';
                            $this->log('not added to AW by name: ' . $p);
                        }
                    }
                    $parts['email'] = $this->removeStrBySeparator($parts['email'], $Emails4saved);
                    $this->log("parts['email'] after check name(after saving by name): " . $parts['email']);

                    if (!empty($parts['email'])) {
                        $emails = explode(",", $parts['email']);

                        foreach ($emails as $email) {
                            $pe = mb_strtolower(str_replace(" ", "", $email));
                            $q = $this->em->getConnection()->executeQuery("select u.Email, ua.UserAgentID, ua.ClientID from UserAgent ua, Usr u
							where ua.AgentID = ? and ua.ClientID = u.UserID 
								and LOWER(u.Email) = ?
								and ua.IsApproved
							union all 
							select ua.Email, ua.UserAgentID, ua.ClientID from UserAgent ua
								where ua.AgentID = ? and LOWER(ua.Email) = ?
							", [$businessID, $pe, $businessID, $pe]);

                            if ($q->rowCount() === 1) {
                                $rowUA = $q->fetch(\PDO::FETCH_ASSOC);
                                $res = $this->savingForMemberBusiness($rowUA, $toSave, $user, $token, $info->messageId, $info->source, $data->providerCode);

                                if ($res) {
                                    $addedItinerary = true;
                                    $SummaryLog .= '{itinerary added to AW by email: ' . $pe . '}';
                                    $this->log('itinerary added to AW by email: ' . $pe);
                                    $result = self::SAVE_MESSAGE_SUCCESS;
                                }
                            } else {
                                $SummaryLog .= '{not added to AW by email: ' . $pe . '}';
                                $this->log('not added to AW by email: ' . $pe);
                            }
                        }
                    }
                } elseif ($cancelled) {
                    $result = self::SAVE_MESSAGE_SUCCESS;
                }
            }
        }

        if ($addedItinerary) {
            $mess = 'INFO ADDED TO AW';
        } else {
            $mess = 'INFO NOT ADDED TO AW';
        }
        $this->logger->info($mess, ["worker" => "businessEmail", "businessID" => $businessID, "userData" => $data->userData, "summaryLog" => $SummaryLog]);

        return $result;
    }

    public function checkBusinessByLogin($businessName)
    {
        $r = $this->userRep->getBusinessIdByLogin($businessName);

        if ($r !== false) {
            $this->log("get business Id = " . $r);
        }

        return $r;
    }

    public function isBusinessById($id)
    {
        return $this->userRep->isBusinessById($id);
    }

    public function log4checker($s)
    {
        $this->log($s);
    }

    public function log($s, $businessID = null, $userData = null)
    {
        if ($userData == null) {
            $userData = $this->userData;
        }

        if ($businessID == null) {
            $businessID = $this->businessID;
        }

        // for report for business
        if (preg_match("#^not added to AW by email:\s+(.*)$#", $s, $m)) {
            $this->logger->info($m[1], ["worker" => "businessEmail", "businessID" => $businessID, "userData" => $userData, "extended" => "not added to AW by email"]);
        } elseif (preg_match("#^not added to AW by name:\s+(.*)$#", $s, $m)) {
            $this->logger->info($m[1], ["worker" => "businessEmail", "businessID" => $businessID, "userData" => $userData, "extended" => "not added to AW by name"]);
        } else {
            $this->logger->info($s, ["worker" => "businessEmail", "businessID" => $businessID, "userData" => $userData]);
        }
    }

    protected function savingForMemberBusiness(
        array $rowUA,
        array $toSave,
        Usr $user,
        $token,
        string $messageId,
        ?ParsedEmailSource $source,
        ?string $providerCode
    ): bool {
        if (empty($rowUA['ClientID'])) {
            // not connected
            $userAgent = null;

            if (!empty($rowUA['UserAgentID'])) {
                $userAgent = $this->userAgentRep->findOneByUseragentid($rowUA['UserAgentID']);
            }
            $options = SavingOptions::savingByEmail(
                OwnerRepository::getOwner($user, $userAgent),
                $messageId,
                $source,
                true,
                false,
                false,
                null,
                $providerCode
            );
            $report = $this->saver->save($toSave, $options);
        } else {
            // connected
            $useragent = $this->userAgentRep->findOneByUseragentid($rowUA['UserAgentID']);
            $client = $this->userRep->findOneByUserid($rowUA['ClientID']);

            if ($this->userAgentVoter->editTimeline($token, $useragent)) {
                $str = 'true';
            } else {
                $str = 'false';
            }
            $this->log("(editTimeline) ClientID={$rowUA['ClientID']} for UserAgentID=" . $rowUA['UserAgentID'] . ' is ' . $str);

            if (isset($rowUA['ClientID']) && !empty($rowUA['ClientID']) && $this->userAgentVoter->editTimeline($token, $useragent)) {
                $options = SavingOptions::savingByEmail(
                    OwnerRepository::getOwner($client, null),
                    $messageId,
                    $source,
                    true,
                    false,
                    false,
                    null,
                    $providerCode
                );
                $report = $this->saver->save($toSave, $options);
            } else {
                $this->log("(editTimeline) ClientID={$rowUA['ClientID']}, not saved for UserAgentID=" . $rowUA['UserAgentID']);
            }
        }

        return isset($report) && (count($report->getUpdated()) + count($report->getAdded()) + count($report->getRemoved()) > 0);
    }

    /**
     * @param Schema\Itinerary[] $itineraries
     * @return array
     */
    protected function getTravellers(array $itineraries)
    {
        $pax = [];

        foreach ($itineraries as $it) {
            switch (get_class($it)) {
                case Schema\Flight::class:
                    /** @var Schema\Flight $it */
                    $this->addPax($it->travelers ?? [], $pax);

                    break;

                case Schema\HotelReservation::class:
                    /** @var Schema\HotelReservation $it */
                    $this->addPax($it->guests ?? [], $pax);

                    break;

                case Schema\CarRental::class:
                    /** @var Schema\CarRental $it */
                    $this->addPax([$it->driver], $pax);

                    break;

                case Schema\Bus::class:
                case Schema\Train::class:
                case Schema\Transfer::class:
                    /** @var Schema\Transportation $it */
                    $this->addPax($it->travelers ?? [], $pax);

                    break;

                case Schema\Cruise::class:
                    /** @var Schema\Cruise $it */
                    $this->addPax($it->travelers ?? [], $pax);

                    break;

                case Schema\Event::class:
                    /** @var Schema\Event $it */
                    $this->addPax($it->guests ?? [], $pax);

                    break;

                case Schema\Parking::class:
                    /** @var Schema\Parking $it */
                    $this->addPax([$it->owner], $pax);

                    break;
            }
        }
        $pax = explode(',', implode(',', $pax));
        $pax = array_filter(array_unique($pax));

        return $pax;
    }

    /**
     * parseBusinessAddress could be like Util->parseAddress and..., for now just exclude admins emails and save user email.
     */
    protected function parseBusinessAddress($to, array $adminEmails)
    {
        $to = str_replace("+", "", $to);
        // exclude admins
        $to = $this->removeStrBySeparator($to, $adminEmails);
        // $parts = $this->util->parseAddress($to);//if it will be need, so just uncomment
        $parts['email'] = $to;

        return $parts;
    }

    /**
     * @param Schema\Person[] $src
     */
    private function addPax(array $src, array &$dest)
    {
        if (!empty($src)) {
            foreach ($src as $person) {
                if ($person) {
                    $dest[] = $person->name;
                }
            }
        }
    }

    /*
     * removeStrBySeparator - Removing the substrings specified by the separator
     */
    private function removeStrBySeparator($haystack, $needle, $separator = ',')
    {
        $str = str_replace(" ", "", $haystack);

        if (is_string($needle)) {
            $needle = explode($separator, mb_strtolower($needle));
        }
        $needle = array_map("trim", $needle);

        foreach ($needle as $e) {
            $pattern = "#(?:^\s*|\s*{$separator})" . str_replace('.', '\.', $e) . "(?:\s*{$separator}|\s*$)#iu";
            $str = preg_replace($pattern, $separator, $str);
        }

        $str = str_replace(" ", "", $str);
        $str = implode($separator, array_filter(explode($separator, mb_strtolower($str))));

        return $str;
    }
}
