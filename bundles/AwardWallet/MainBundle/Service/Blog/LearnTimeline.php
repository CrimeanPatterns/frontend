<?php

namespace AwardWallet\MainBundle\Service\Blog;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Blog\Model\PostItem;
use AwardWallet\MainBundle\Service\FlightSearch\PlaceQuery;
use AwardWallet\MainBundle\Service\Tip\Definition\TimelineLink;
use AwardWallet\MainBundle\Timeline\Manager as TimelineManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class LearnTimeline
{
    private EntityManagerInterface $entityManager;
    private TimelineManager $timelineManager;
    private \Memcached $memcached;
    private RouterInterface $router;

    public function __construct(
        EntityManagerInterface $entityManager,
        TimelineManager $timelineManager,
        \Memcached $memcached,
        RouterInterface $router
    ) {
        $this->entityManager = $entityManager;
        $this->timelineManager = $timelineManager;
        $this->memcached = $memcached;
        $this->router = $router;
    }

    public function getTimelineData(Usr $user): array
    {
        $queryOptions = TimelineLink::getTipQueryOptions($user);

        return $this->timelineManager->query($queryOptions);
    }

    public function fetchFlights(Usr $user): array
    {
        $result = [];
        $timeline = $this->getTimelineData($user);

        foreach ($timeline as $data) {
            if ('segment' !== $data['type']
                || Trip::SEGMENT_MAP . '.' !== substr($data['id'], 0, 2)
            ) {
                continue;
            }

            $dep = $data['map']['points'][0] ?? null;
            $arr = $data['map']['points'][1] ?? null;

            if (null === $dep || null === $arr
                || 3 !== strlen($dep) || 3 !== strlen($arr)
            ) {
                continue;
            }

            $item = [
                'route' => [
                    'dep' => $dep,
                    'arr' => $arr,
                ],
                'date' => [
                    'dep' => $data['localDateTimeISO'],
                    'arr' => date('c', $data['endDate']),
                ],
                'time' => [
                    'dep' => $data['localTime'],
                    'arr' => $data['map']['arrTime'],
                ],
                'timezone' => [
                    'dep' => $data['startTimezone'],
                    'arr' => $data['endTimezone'],
                ],
                'place' => [
                    'dep' => $data['details']['columns'][0]['rows'][0]['text']['place'] ?? '',
                    'arr' => $data['details']['columns'][2]['rows'][0]['text']['place'] ?? '',
                ],
                // 'duration' => $data['duration'],
                'thumb' => $this->router->generate('aw_flight_map', [
                    'code' => $dep . '-' . $arr,
                    'size' => '240x240',
                ]), // /assets/awardwalletnewdesign/img/map@2x.png
            ];

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @return PostItem[]
     */
    public function assignPostWithFlights($posts, $flights): array
    {
        $airportsId = [];
        $postWithRoutes = [];

        foreach ($posts as $post) {
            if (empty($post->getTags())) {
                continue;
            }

            foreach ($post->getTags() as $tag) {
                if (!property_exists($tag, 'meta')
                    || !property_exists($tag->meta, Constants::META_AW_DEP_KEY)
                    || !property_exists($tag->meta, Constants::META_AW_ARR_KEY)
                ) {
                    continue;
                }

                if (PlaceQuery::TYPE_AIRPORT === (int) $tag->meta->{Constants::META_AW_DEP_KEY}->type
                    && PlaceQuery::TYPE_AIRPORT === (int) $tag->meta->{Constants::META_AW_ARR_KEY}->type
                ) {
                    $airportsId[] = (int) $tag->meta->{Constants::META_AW_DEP_KEY}->id;
                    $airportsId[] = (int) $tag->meta->{Constants::META_AW_ARR_KEY}->id;
                }
            }
        }

        if (!empty($airportsId)) {
            $airports = $this->entityManager->getConnection()->fetchAllAssociative('
                SELECT AirCodeID, AirCode, AirName
                FROM AirCode
                WHERE AirCodeID IN (?)
                ',
                [$airportsId],
                [Connection::PARAM_INT_ARRAY]
            );
            $airports = array_column($airports, null, 'AirCodeID');
        }

        if (empty($airports)) {
            return $posts;
        }

        foreach ($posts as &$post) {
            foreach ($post->getTags() as $tag) {
                if (!property_exists($tag, 'meta')
                    || !property_exists($tag->meta, Constants::META_AW_DEP_KEY)
                    || !property_exists($tag->meta, Constants::META_AW_ARR_KEY)
                ) {
                    continue;
                }

                $depAirId = (int) $tag->meta->{Constants::META_AW_DEP_KEY}->id;
                $arrAirId = (int) $tag->meta->{Constants::META_AW_ARR_KEY}->id;

                if (null === ($airports[$depAirId] ?? null) || null === ($airports[$arrAirId] ?? null)) {
                    $this->logger->critical(
                        'blog-learn-travels unknown air id',
                        ['depId' => $depAirId, 'arrId' => $arrAirId]
                    );

                    continue;
                }

                $post
                    ->setMeta(Constants::META_AW_DEP_KEY, $airports[$depAirId])
                    ->setMeta(Constants::META_AW_ARR_KEY, $airports[$arrAirId]);

                $postRoute = $airports[$depAirId]['AirCode'] . '-' . $airports[$arrAirId]['AirCode'];

                foreach ($flights as $flight) {
                    $flightRoute = $flight['route']['dep'] . '-' . $flight['route']['arr'];

                    if (strtolower($postRoute) === strtolower($flightRoute)) {
                        $post->setMeta(Constants::META_FLIGHT_ROUTE_KEY, $flight);
                    }
                }
            }
        }

        return $posts;
    }
}
