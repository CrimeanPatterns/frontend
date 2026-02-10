<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Timeline\Diff\Query;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/itineraryInfo")
 */
class ItineraryInfoController extends AbstractController
{
    public const TYPE_HTML = 'html';

    /**
     * @Route("/{segmentId}", name="aw_manager_itineraryinfo_info", requirements={"segmentId" = "(T|R|L|E|P)\.\d+"})
     */
    public function infoAction(string $segmentId, EntityManagerInterface $em, Query $query)
    {
        [$kind, $id] = explode('.', $segmentId);
        $map = Itinerary::$table;
        $error = function (string $errText) {
            return new JsonResponse($this->renderView('@AwardWalletMain/Manager/ItineraryInfo/notFound.html.twig', [
                'error' => $errText,
            ]));
        };

        if (!isset($map[$kind])) {
            return $error('Unknown itinerary kind');
        }

        $table = $kind === 'T' ? 'Tripsegment' : $map[$kind];
        $itinerary = $em->getRepository(Itinerary::getItineraryClass($table))->find($id);

        if (!$itinerary) {
            return $error('Itinerary not found');
        }

        switch ($kind) {
            case 'T':
                $checkRole = 'ROLE_MANAGE_TRIPINFO';

                break;

            case 'R':
                $checkRole = 'ROLE_MANAGE_RESERVATIONINFO';

                break;

            case 'L':
                $checkRole = 'ROLE_MANAGE_RENTALINFO';

                break;

            case 'E':
                $checkRole = 'ROLE_MANAGE_RESTAURANTINFO';

                break;

            case 'P':
                $checkRole = 'ROLE_MANAGE_PARKINGINFO';

                break;

            default:
                return $error('Unknown itinerary kind');
        }

        if (!$this->isGranted($checkRole)) {
            return $error(sprintf('%s: No rights to view information', $checkRole));
        }

        $changedPropsQuery = $query->query(str_ireplace('T', 'S', $segmentId));
        $minChangeDate = new \DateTime('-10 year');
        $segment = null;

        if ($itinerary instanceof Tripsegment) {
            $segment = $itinerary;
            $itinerary = $itinerary->getTripid();
        }

        return new JsonResponse(
            $this->renderView('@AwardWalletMain/Manager/ItineraryInfo/info.html.twig', [
                'info' => $this->getItineraryInfo($itinerary, $segment),
                'changedProps' => array_filter(array_map(function (string $propName) use ($changedPropsQuery, $minChangeDate) {
                    $changedProp = $changedPropsQuery->getChangedProperty($propName, $minChangeDate);

                    if (is_null($changedProp)) {
                        return null;
                    }

                    return array_merge($changedProp, ['Name' => $propName]);
                }, $changedPropsQuery->getChangedProperties($minChangeDate))),
            ])
        );
    }

    private function getItineraryInfo(Itinerary $itinerary, ?Tripsegment $segment): array
    {
        $providerPattern = '%s (<a target="_blank" href="/manager/list.php?Schema=Provider&ProviderID=%s">#%s</a>)';

        $info = [
            'Itinerary Id' => $itinerary->getIdString(),
            'Create Date' => $this->formatDate($itinerary->getCreateDate()),
            'Update Date' => $this->formatDate($itinerary->getUpdateDate()),
            'Reservation Date' => $this->formatDate($itinerary->getReservationDate()),
            'Undeleted' => $this->formatBool($itinerary->isUndeleted()),
            'Parsed' => $this->formatBool($itinerary->getParsed()),
            'Modified' => $this->formatBool($itinerary->getModified()),
            'Copied' => $this->formatBool($itinerary->getCopied()),
            'Cancelled' => $this->formatBool($itinerary->getCancelled()),
            'Parsed Status' => $itinerary->getParsedStatus(),
            'Travelers' => !empty($itinerary->getTravelerNames()) ? implode(', ', $itinerary->getTravelerNames()) : null,
            'Travel Agency' => $this->typedHtml(($p = $itinerary->getTravelAgency()) ? sprintf($providerPattern, $p->getDisplayname(), $p->getId(), $p->getId()) : null),
            'Spent Awards' => ($pi = $itinerary->getPricingInfo()) ? $pi->getSpentAwards() : null,
            'Spent Awards Provider' => $this->typedHtml(($p = $itinerary->getSpentAwardsProvider()) ? sprintf($providerPattern, $p->getDisplayname(), $p->getId(), $p->getId()) : null),
            'Earned Awards' => ($pi = $itinerary->getPricingInfo()) ? $pi->getEarnedAwards() : null,
        ];

        if ($segment && $segment->getFlightinfoid()) {
            $fi = $segment->getFlightinfoid();
            $info['Flight Info'] = $this->typedHtml(sprintf('
                State: %s<br>
                Flight State: %s<br>
                <a href="%s" target="_blank">Details #%d</a>
            ', FlightInfoController::STATES[$fi->getState()] ?? 'Unknown',
                FlightInfoController::FLIGHT_STATES[$fi->getFlightState()] ?? 'Unknown',
                $this->generateUrl('aw_manager_flightinfo_view', ['id' => $fi->getFlightInfoID()]),
                $fi->getFlightInfoID()
            ));
        }

        // geotags
        if ($segment) {
            if ($depgt = $segment->getDepgeotagid()) {
                $info['Dep GeoTag'] = $this->formatGeoTag($depgt);
            }

            if ($arrgt = $segment->getArrgeotagid()) {
                $info['Arr GeoTag'] = $this->formatGeoTag($arrgt, $depgt ?? null);
            }
        } else {
            $prevGeotag = null;

            foreach ($itinerary->getGeoTags() as $k => $geotag) {
                $info['GeoTag #' . ($k + 1)] = $this->formatGeoTag($geotag, $prevGeotag);
                $prevGeotag = $geotag;
            }
        }

        return array_filter($info);
    }

    private function formatDate(?\DateTime $dateTime): ?string
    {
        return $dateTime ? $dateTime->format('Y-m-d H:i:s') : null;
    }

    private function formatBool(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    private function formatGeoTag(Geotag $geotag, ?Geotag $prevGeotag = null): array
    {
        $data = [];

        if (!empty($geotag->getAddress())) {
            $data['Address'] = $geotag->getAddress();
        }

        if (!empty($geotag->getCountry())) {
            $data['Country'] = $geotag->getCountry();
        }

        if (!empty($geotag->getCountryCode())) {
            $data['Country Code'] = $geotag->getCountryCode();
        }

        if (!empty($geotag->getCity())) {
            $data['City'] = $geotag->getCity();
        }

        if (!empty($geotag->getState())) {
            $data['State'] = $geotag->getState();
        }

        if (!empty($geotag->getTimeZoneLocation())) {
            $data['Time Zone'] = $geotag->getTimeZoneLocation();
        }

        if (!empty($geotag->getUpdatedate())) {
            $data['Update Date'] = $this->formatDate($geotag->getUpdatedate());
        }

        if (!empty($geotag->getLat())) {
            $data['Latitude'] = $geotag->getLat();
        }

        if (!empty($geotag->getLng())) {
            $data['Longitude'] = $geotag->getLng();
        }

        if (!empty($geotag->getLat()) && !empty($geotag->getLng())) {
            $data['Map'] = sprintf('<a href="https://www.google.com/maps/search/?api=1&query=%s,%s" target="_blank">Google Maps</a>', $geotag->getLat(), $geotag->getLng());
        }

        if ($prevGeotag) {
            $distance = $geotag->distanceFrom($prevGeotag);

            if ($distance !== PHP_INT_MAX) {
                $data['Distance'] = round($distance, 2) . ' miles';
            }
        }

        $string = implode('<br>', array_map(function (string $key, string $value) {
            return sprintf('<b>%s</b>: %s', $key, $value);
        }, array_keys($data), array_values($data)));

        return $this->typedHtml($string);
    }

    private function typedHtml(?string $data): ?array
    {
        if (is_null($data)) {
            return null;
        }

        return [
            'type' => self::TYPE_HTML,
            'data' => $data,
        ];
    }
}
