<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\Common\API\Email\V2\BoardingPass\BoardingPass;
use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\TripsegmentRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class BoardingPassProcessor
{
    /** @var LoggerInterface */
    private $logger;
    /** @var Connection */
    private $db;
    /** @var EntityManager */
    private $em;
    /** @var TripsegmentRepository */
    private $tsr;
    /** @var Router */
    private $router;
    /** @var S3Client */
    private $s3;
    /** @var string */
    private $bucket;

    public function __construct(
        LoggerInterface $logger,
        Connection $db,
        EntityManager $em,
        TripsegmentRepository $tsr,
        Router $router,
        S3Client $s3,
        $bucket)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->em = $em;
        $this->tsr = $tsr;
        $this->router = $router;
        $this->s3 = $s3;
        $this->bucket = $bucket;
    }

    /**
     * @param BoardingPass[] $bps
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process(array $bps, \PlancakeEmailParser $email, Owner $owner): int
    {
        $cnt = 0;

        foreach ($bps as $bp) {
            $ts = $this->match($bp, $owner);

            if (!$ts) {
                continue;
            }
            $url = $bp->boardingPassUrl;

            if (empty($url) && !empty($bp->attachmentFileName)) {
                $url = $this->uploadAndGetUrl($ts, $email, $bp->attachmentFileName);
            }

            if (!empty($url)) {
                $ts->setBoardingpassurl($url);
                $this->em->flush();
                $cnt++;
            }
        }

        return $cnt > 0;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function match(BoardingPass $bp, Owner $owner): ?Tripsegment
    {
        // 1 - data, 2 - data and owner
        $match = 0;
        $segmentId = null;
        $air = $fn = null;

        if ($bp->flightNumber && preg_match('/^(?<a>[A-Z][A-Z\d]|[A-Z\d][A-Z])?\s*(?<fn>\d+)$/', $bp->flightNumber, $m)) {
            $air = $m['a'] ?? null;
            $fn = $m['fn'];
        }
        $q = $this->db->executeQuery('select
            ts.TripSegmentID as Id, t.UserAgentID as Fm,
            a1.Code as MCode, ts.FlightNumber as MFn, a2.Code as OCode, ts.OperatingAirlineFlightNumber as OFn,
            ts.DepCode, ts.DepDate
        from TripSegment ts
          left join Trip t on ts.TripID = t.TripID
          left join Airline a1 on ts.AirlineID = a1.AirlineID
          left join Airline a2 on ts.OperatingAirlineID = a2.AirlineID
        where t.UserID = ? and ts.DepDate between adddate(NOW(), -2) and adddate(NOW(), 14)', [$owner->getUser()->getId()]);

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $m = 0;

            if ((!$air || strcasecmp($air, $row['MCode']) === 0) && $fn == $row['MFn']
                || (!$air || strcasecmp($air, $row['OCode']) === 0) && $fn == $row['OFn']) {
                $m = 1;

                if ($owner->getFamilyMember() && $owner->getFamilyMember()->getId() == $row['Fm']
                    || !$owner->getFamilyMember() && empty($row['Fm'])) {
                    $m = 2;
                }

                if ($bp->departureDate && ($ts = strtotime($bp->departureDate)) && $row['DepDate']
                    && abs($ts - strtotime($row['DepDate'])) > DateTimeUtils::SECONDS_PER_HOUR * 2) {
                    $m = 0;
                }

                if ($bp->departureAirportCode && $row['DepCode'] && $bp->departureAirportCode != $row['DepCode']) {
                    $m = 0;
                }
            }

            if ($m === $match) {
                $segmentId = false;
            } elseif ($m > $match) {
                $match = $m;
                $segmentId = $row['Id'];
            }
        }

        if ($match > 0 && false === $segmentId) {
            $this->logger->info('ambiguous segments for bp', ['bp' => $bp, 'userid' => $owner->getUser()->getId()]);
        }

        if (!empty($segmentId)) {
            $this->logger->info('matched bp to a segment', ['bp' => $bp, 'userid' => $owner->getUser()->getId(), 'tripsegmentid' => $segmentId]);

            return $this->tsr->find($segmentId);
        }

        return null;
    }

    private function uploadAndGetUrl(Tripsegment $ts, \PlancakeEmailParser $email, $name): ?string
    {
        $search = $email->searchAttachmentByName(preg_quote($name, '/'));

        if (count($search) === 1) {
            $body = $email->getAttachmentBody($search[0]);
            $ext = substr($name, -3);
            $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => sprintf('bp_%d.%s', $ts->getId(), $ext),
                'Body' => $body,
                'Expires' => new \DateTime('+20 days'),
            ]);

            return $this->router->generate('aw_trips_pass', ['segmentId' => $ts->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return null;
    }
}
