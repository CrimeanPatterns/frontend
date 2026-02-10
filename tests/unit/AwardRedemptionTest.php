<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\Common\API\Converter\V2\Loader;
use AwardWallet\Common\API\Email\V2\Meta\EmailInfo;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Extra\ProviderData;
use AwardWallet\MainBundle\Email\EmailOptions;
use AwardWallet\MainBundle\Email\Util;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Schema\Parser\Component\Options;
use AwardWallet\Schema\Parser\Email\Email;
use AwardWallet\Schema\Parser\Util\ArrayConverter;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class AwardRedemptionTest extends BaseContainerTest
{
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var \Memcached
     */
    private $memcached;

    private int $mailboxId = 1;
    private ?object $processor;
    private ?string $email;
    private ?array $parsed;
    private ?string $locator;

    public function _before()
    {
        parent::_before();
        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [], true /* staff user has access to test provider */);
        $this->assertNotNull($this->user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId));
        $this->memcached = $this->getModule('Symfony')->_getContainer()->get(\Memcached::class);
        $this->processor = $this->getCallbackProcessor();
        $this->email = file_get_contents(__DIR__ . '/../_data/expedia.eml');
        $this->email = str_ireplace("ialabuzheva.123@awardwallet.com", $this->user->getLogin() . '@awardwallet.com', $this->email);

        $this->locator = strtoupper(bin2hex(openssl_random_pseudo_bytes(3)));
        $this->parsed = [
            'metadata' => [
                'mailboxId' => $this->mailboxId,
                'receivedDateTime' => '2023-05-18T08:57:43',
            ],
            'Itineraries' => $this->getItinerariesData(['Kailey Maurer', 'Adam Klein'], $this->locator),
        ];

        $data = $this->buildData($this->email, $this->parsed, 'aa');

        $result = $this->processor->process($data, new EmailOptions($data, false), null, null);

        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
        $this->db->seeInDatabase("Trip", ["RecordLocator" => $this->locator, "UserID" => $this->user->getUserid()]);

        $this->checkAaMatchingCache(
            $this->mailboxId,
            [
                'haveTripId' => true,
                'milesRedeemed' => 0,
                'waitRedemptions' => 2,
                'blocked' => false,
            ]
        );

        unset($this->parsed['Itineraries']);
        $this->parsed['awardRedemption'] = $this->getAwardRedemptionData('KAILEY MAURER');

        $data = $this->buildData($this->email, $this->parsed, 'aa');
        $result = $this->processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);

        $this->checkAaMatchingCache(
            $this->mailboxId,
            [
                'haveTripId' => true,
                'milesRedeemed' => 90000,
                'waitRedemptions' => 1,
                'blocked' => false,
            ]
        );
    }

    public function _after()
    {
        parent::_after();
        $this->user = null;
        $this->memcached->set('aa_matching_reservation_' . $this->mailboxId, null);
        $this->memcached = null;
        $this->processor = null;
        $this->email = null;
        $this->parsed = null;
        $this->locator = null;
    }

    public function testAaMatching()
    {
        $this->parsed['awardRedemption'] = $this->getAwardRedemptionData('ADAM KLEIN');
        $this->parsed['metadata']['receivedDateTime'] = '2023-05-18T09:11:00';

        $aaMatching = $this->memcached->get('aa_matching_reservation_' . $this->mailboxId);
        $aaMatching['reservationDate'] = null;
        $this->memcached->set('aa_matching_reservation_' . $this->mailboxId, $aaMatching, $aaMatching['expiry']);

        $data = $this->buildData($this->email, $this->parsed, 'aa');
        $result = $this->processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);

        $this->checkAaMatchingCache(
            $this->mailboxId,
            [
                'haveTripId' => false,
                'milesRedeemed' => null,
                'waitRedemptions' => null,
                'blocked' => null,
            ]
        );

        $spentAwards = $this->db->grabFromDatabase("Trip", "SpentAwards", ["RecordLocator" => $this->locator, "UserID" => $this->user->getUserid()]);
        $this->assertEquals(
            180000,
            $spentAwards
        );
    }

    public function testAaMatchingExpiry()
    {
        $this->memcached->set('aa_matching_reservation_' . $this->mailboxId, null);

        $this->parsed['awardRedemption'] = $this->getAwardRedemptionData('ADAM KLEIN');

        $data = $this->buildData($this->email, $this->parsed, 'aa');
        $result = $this->processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_FAIL, $result);

        $this->checkAaMatchingCache(
            $this->mailboxId,
            [
                'haveTripId' => false,
                'milesRedeemed' => null,
                'waitRedemptions' => null,
                'blocked' => null,
            ]
        );

        $spentAwards = $this->db->grabFromDatabase("Trip", "SpentAwards", ["RecordLocator" => $this->locator, "UserID" => $this->user->getUserid()]);
        $this->assertNull($spentAwards);
    }

    public function testAaMatchingBlock()
    {
        unset($this->parsed['awardRedemption']);
        $locatorNew = strtoupper(bin2hex(openssl_random_pseudo_bytes(3)));
        $this->parsed['Itineraries'] = $this->getItinerariesData(['Adam Klein'], $locatorNew);

        $data = $this->buildData($this->email, $this->parsed, 'aa');
        $result = $this->processor->process($data, new EmailOptions($data, false), null, null);

        $this->assertEquals(Util::SAVE_MESSAGE_SUCCESS, $result);
        $this->db->seeInDatabase("Trip", ["RecordLocator" => $locatorNew, "UserID" => $this->user->getUserid()]);

        $this->checkAaMatchingCache(
            $this->mailboxId,
            [
                'haveTripId' => false,
                'milesRedeemed' => null,
                'waitRedemptions' => 0,
                'blocked' => true,
            ]
        );

        unset($this->parsed['Itineraries']);
        $this->parsed['awardRedemption'] = $this->getAwardRedemptionData('ADAM KLEIN');

        $data = $this->buildData($this->email, $this->parsed, 'aa');
        $result = $this->processor->process($data, new EmailOptions($data, false), null, null);
        $this->assertEquals(Util::SAVE_MESSAGE_FAIL, $result);

        $this->checkAaMatchingCache(
            $this->mailboxId,
            [
                'haveTripId' => false,
                'milesRedeemed' => null,
                'waitRedemptions' => 0,
                'blocked' => true,
            ]
        );
    }

    private function getCallbackProcessor()
    {
        $this->mockServiceWithBuilder('aw.email.mailer')->method('send')->willReturn(true);
        $this->mockServiceWithBuilder('aw.solver.alias_provider.aircode');

        return $this->container->get('aw.email.callback_processor');
    }

    private function buildData($emailSource, ?array $parsed, $providerCode): ParseEmailResponse
    {
        $data = new ParseEmailResponse();
        $data->itineraries = [];

        if (isset($emailSource)) {
            $data->email = base64_encode($emailSource);
        }

        if (isset($parsed)) {
            $email = new Email('e', new Options());
            ArrayConverter::convertMaster($parsed, $email);
            $email->setProviderCode($providerCode);
            $extra = new Extra();
            $extra->provider = ProviderData::fromArray($this->container->get('database_connection')->fetchAssoc('select * from Provider where Code = ?', [$providerCode], [\PDO::PARAM_STR]));
            $extra->context->partnerLogin = '';
            $this->container->get('aw.solver.master')->solve($email, $extra);
            $converter = new Loader();

            foreach ($email->getItineraries() as $it) {
                $data->itineraries[] = $converter->convert($it, $extra);
            }

            foreach ($email->getBPasses() as $bp) {
                $new = new \AwardWallet\Common\API\Email\V2\BoardingPass\BoardingPass();
                $new->departureAirportCode = $bp->getDepCode() ?? null;
                $new->flightNumber = $bp->getFlightNumber() ?? null;
                $new->boardingPassUrl = $bp->getUrl() ?? null;
                $data->boardingPasses[] = $new;
            }

            foreach ($email->getAwardRedemption() as $ar) {
                $r = new \AwardWallet\Common\API\Email\V2\AwardRedemption\AwardRedemption();
                $r->dateIssued = \AwardWallet\Common\API\Converter\V2\Util::date($ar->getDateIssued()) ?? null;
                $r->milesRedeemed = $ar->getMilesRedeemed() ?? null;
                $r->recipient = $ar->getRecipient() ?? null;
                $r->description = $ar->getDescription() ?? null;
                $r->accountNumber = $ar->getAccountNumber() ?? null;
                $data->awardRedemption[] = $r;
            }

            if (isset($parsed['metadata'])) {
                $metadata = new EmailInfo();

                if (isset($parsed['metadata']['mailboxId'])) {
                    $metadata->mailboxId = $parsed['metadata']['mailboxId'];
                }

                if (isset($parsed['metadata']['receivedDateTime'])) {
                    $metadata->receivedDateTime = $parsed['metadata']['receivedDateTime'];
                }
                $data->metadata = $metadata;
            }

            if ($providerCode) {
                $data->providerCode = $providerCode;
            }
        }

        return $data;
    }

    private function checkAaMatchingCache(int $mailboxId, array $params)
    {
        $aaMatching = $this->memcached->get('aa_matching_reservation_' . $mailboxId);

        $id = $aaMatching['trip']['id'] ?? null;
        $milesRedeemed = $aaMatching['trip']['milesRedeemed'] ?? null;
        $waitRedemptions = $aaMatching['waitRedemptions'] ?? null;
        $blocked = $aaMatching['blocked'] ?? false;

        if ($params['haveTripId']) {
            $this->assertNotNull($id);
            $this->db->seeInDatabase("Trip", ["TripID" => $id]);
        } else {
            $this->assertNull($id);
        }

        $this->assertEquals($milesRedeemed, $params['milesRedeemed']);
        $this->assertEquals($waitRedemptions, $params['waitRedemptions']);
        $this->assertEquals($blocked, $params['blocked']);
    }

    private function getItinerariesData(array $passengers, string $locator): array
    {
        return [
            [
                'TripSegments' => [
                    0 => [
                        'FlightNumber' => '12345',
                        'DepDate' => strtotime("-1 month 14:00"),
                        'DepCode' => 'DEN',
                        'DepName' => 'Denver Co',
                        'ArrDate' => strtotime("-1 month 16:00"),
                        'ArrCode' => 'PHX',
                        'ArrName' => 'Phoenix Az',
                        'AirlineName' => 'DL',
                    ],
                ],
                'RecordLocator' => $locator,
                'Kind' => 'T',
                'Passengers' => $passengers,
                'ReservationDate' => strtotime("2023-05-18T00:00:00"),
            ],
        ];
    }

    private function getAwardRedemptionData($traveler): array
    {
        return [
            [
                'dateIssued' => strtotime("2023-05-18T00:00:00"),
                'milesRedeemed' => 60000,
                'recipient' => $traveler,
                'description' => 'Flight award',
                'accountNumber' => '34MWB40',
            ],
            [
                'dateIssued' => strtotime("2023-05-18T00:00:00"),
                'milesRedeemed' => 30000,
                'recipient' => $traveler,
                'description' => 'Flight award',
                'accountNumber' => '34MWB40',
            ],
        ];
    }
}
