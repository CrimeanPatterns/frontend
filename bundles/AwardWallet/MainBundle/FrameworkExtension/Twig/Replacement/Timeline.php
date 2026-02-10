<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\Replacement;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\Twig\Replacement;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\Tip\Definition\TimelineLink;
use AwardWallet\MainBundle\Timeline\Manager as TimelineManager;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFunction;

class Timeline extends TwigExtension
{
    /** @var TimelineManager */
    protected $timelineManager;

    /** @var LocalizeService */
    protected $localizeService;

    /** @var DateTimeIntervalFormatter */
    protected $intervalFormatter;

    /** @var AwTokenStorage */
    private $tokenStorage;

    /** @var array */
    private $data;

    public function __construct(
        AwTokenStorage $tokenStorage,
        TimelineManager $timelineManager,
        LocalizeService $localizeService,
        DateTimeIntervalFormatter $intervalFormatter
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->timelineManager = $timelineManager;
        $this->localizeService = $localizeService;
        $this->intervalFormatter = $intervalFormatter;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('Timeline', function () {
                /** @var Usr $user */
                $user = $this->tokenStorage->getUser();
                $uid = $user->getId();

                if (null === $this->data || !array_key_exists($uid, $this->data)) {
                    $queryOptions = TimelineLink::getTipQueryOptions($user);
                    $this->data[$uid] = $this->timelineManager->query($queryOptions);
                }

                $next = $this->getNext($this->data[$uid]);
                $nextPlan = $this->getNextPlan($this->data[$uid]);

                if (isset($next['startDate'], $nextPlan['first']['startDate']) && !empty($nextPlan) && $nextPlan['first']['startDate'] <= $next['startDate']) {
                    $next = $nextPlan;
                }

                if (empty($next)) {
                    return [
                        'next' => [
                            'type' => '',
                            'name' => '',
                            'date' => '',
                            'timeAhead' => '',
                            'location' => '',
                        ],
                    ];
                }

                $startDateTime = new \DateTime($next['first']['localDateTimeISO']);
                $endDateTime = new \DateTime($next['last']['localDateTimeISO']);

                $date = $this->fetchDate($next, $startDateTime, $endDateTime);
                $timeAhead = $this->fetchTimeAhead($next, $startDateTime, $endDateTime);
                $result = [
                    'next' => [
                        'type' => $next['_type'],
                        'name' => $this->strReplaceTag($next['name'] ?? $next['title'] ?? '', ' '),
                        'date' => $this->localizeService->formatDateTime($date, 'long'),
                        // 'startDate'      => $this->localizeService->formatDateTime($startDateTime, 'long'),
                        // 'endDate'        => $this->localizeService->formatDateTime($endDateTime, 'long'),
                        'timeAhead' => '<span class="js-date-utc" data-date="' . $timeAhead->format('U') . '" data-format="diffTimeAgo">' . $this->intervalFormatter->longFormatViaDateTimes(
                            new \DateTime(),
                            $timeAhead
                        ) . '</span>',
                        // 'startTimeAhead' => '<span class="js-date-utc" data-date="' . $next['first']['startDate'] . '" data-format="diffTimeAgo">' . $this->timeago->get(new \DateTime(), $startDateTime) . '</span>',
                        // 'endTimeAhead'   => '<span class="js-date-utc" data-date="' . $endDateTime->format('U') . '" data-format="diffTimeAgo">' . $this->timeago->get(new \DateTime(), $endDateTime) . '</span>',
                        'location' => trim($this->fetchLocation($next), ',.'),
                        // 'startLocation'  => $this->fetchLocation($next, 'first', 'start'),
                        // 'endLocation'    => $this->fetchLocation($next, 'last', 'end'),
                    ],
                ];
                !empty($result['next']['location']) ?: $result['next']['location'] = $result['next']['name'];

                return Replacement::contextMarkup($result);
            }, ['is_safe' => ['html']]),
        ];
    }

    private function isTrip($data, $step = 'first'): bool
    {
        return array_key_exists($step, $data) && Trip::getSegmentMap()[0] === $this->getType($data[$step]['id']);
    }

    private function getType($value)
    {
        return explode('.', $value)[0];
    }

    private function fetchTimeAhead($data, $startDateTime, $endDateTime): \DateTime
    {
        $date = $this->fetchDate($data, $startDateTime, $endDateTime);

        if (\in_array($data['_type'], [Rental::getSegmentMap()[0], Reservation::getSegmentMap()[0]])) {
            return new \DateTime('@' . $data['first']['startDate']);
        }

        return $date;
    }

    private function fetchDate($data, $startDateTime, $endDateTime): \DateTime
    {
        if ($this->isTrip($data)) {
            if (isset($data['first']['details']['columns'][2]['rows'][1]['timestamp'])) {
                $dateTime = (new \DateTime())->setTimestamp($data['first']['details']['columns'][2]['rows'][1]['timestamp']);

                if (isset($data['first']['details']['columns'][2]['rows'][1]['timezone'])) {
                    $dateTime->setTimezone(new \DateTimeZone($data['first']['details']['columns'][2]['rows'][1]['timezone']));
                }

                return $dateTime;
            }

            if (isset($data['first']['details']['columns'][2]['rows'][1]['formattedDate']) && isset($data['first']['details']['columns'][2]['rows'][1]['time'])) {
                // return new \DateTime($data['first']['details']['columns'][2]['rows'][1]['formattedDate'] . ' ' . $data['first']['details']['columns'][2]['rows'][1]['time']); // problem with other language
            }

            return $endDateTime;
        }

        if (Rental::getSegmentMap()[0] === $data['_type']) {
            if (isset($data['last']['group']) && 'L' === substr($data['last']['group'], 0, 1)) {
                return new \DateTime('@' . $data['last']['endDate']);
            }

            // return new \DateTime('@' . $data['first']['startDate']);
            if (isset($data['last']['localDateTimeISO'])) {
                return new \DateTime($data['last']['localDateTimeISO']);
            }
        }

        if (isset($data['first']['details']['columns'][0]['rows'][1]['formattedDate']) && isset($data['first']['details']['columns'][0]['rows'][1]['time'])) {
            return new \DateTime($data['first']['details']['columns'][0]['rows'][1]['formattedDate'] . ' ' . $data['first']['details']['columns'][0]['rows'][1]['time']);
        }

        if (isset($data['first']['details']['columns'][0]['rows'][1]['timestamp'])) {
            $dateTime = (new \DateTime())->setTimestamp($data['first']['details']['columns'][0]['rows'][1]['timestamp']);

            if (isset($data['first']['details']['columns'][0]['rows'][1]['timezone'])) {
                $dateTime->setTimezone(new \DateTimeZone($data['first']['details']['columns'][0]['rows'][1]['timezone']));
            }

            return $dateTime;
        }

        return $startDateTime;
    }

    private function fetchLocation($data, $stepArg = null, $placeArg = null): ?string
    {
        $step = $stepArg ?? 'first';
        $place = 'start' === $placeArg ? 0 : 2;

        if ($this->isTrip($data, $step)) {
            return $data[$step]['details']['columns'][$place]['rows'][0]['text']['place']
                ?? $data[$step]['details']['columns'][0]['rows'][0]['text']['place']
                ?? null;
        }

        if (Reservation::getSegmentMap()[0] === $data['_type']
            || Restaurant::getSegmentMap()[0] === $data['_type']) {
            $geo = $this->findColumnData('geo', $data['first']['details']['columns'][0]['rows'] ?? []);

            if (!empty($geo['city'])) {
                $parts = [$geo['city']];

                if ('United States' === $geo['country']) {
                    $parts[] = $geo['state'];
                }
                $parts[] = $geo['country'];

                return implode(', ', array_unique($parts));
            }

            return $data['first']['details']['columns'][0]['rows'][1]['text'] ?? null;
        } elseif (Rental::getSegmentMap()[0] === $data['_type']) {
            if (null === $stepArg) {
                if (isset($data['last']['group']) && 'L' === substr($data['last']['group'], 0, 1)) {
                    return $data['first']['details']['columns'][2]['rows'][1]['text']
                        ?? $data['first']['details']['columns'][0]['rows'][1]['text']
                        ?? null;
                }

                return $data['last']['details']['columns'][0]['rows'][1]['text']
                    ?? $data['first']['details']['columns'][2]['rows'][1]['text']
                    ?? $data['first']['details']['columns'][0]['rows'][1]['text']
                    ?? null;
            }

            return $data[$step]['details']['columns'][2]['rows'][1]['text']
                ?? $data[$step]['details']['columns'][0]['rows'][1]['text']
                ?? null;
        }

        return $data[$step]['details']['columns'][0]['rows'][0]['text']['place'] ?? null;
    }

    private function getNext($data): array
    {
        $result = [];

        for ($i = 0, $iCount = \count($data); $i < $iCount; $i++) {
            $_type = $this->getType($data[$i]['id']);

            if ('L' !== $_type
                && 'segment' === $data[$i]['type']
                && time() < $data[$i]['startDate']) {
                $slice = array_slice($data, $i);

                if (empty($result)
                    && !in_array($_type, [Reservation::getSegmentMap()[1], Rental::getSegmentMap()[1]])
                    && $this->isStoping($slice)
                    && ('T' !== $this->getType($data[$i]['id']) || !empty($stop = $this->detectNextSegmentNotTransfer($slice)))
                ) {
                    $next = $stop ?? $data[$i];
                    $result['_type'] = $this->getType($next['id']);
                    $result['name'] = $next['title'] ?? '';
                    $result['first'] = $next;

                    if (Trip::getSegmentMap()[0] === $result['_type']
                        || Restaurant::getSegmentMap()[0] === $result['_type']) {
                        $result['last'] = $next;

                        return $result;
                    }
                } elseif (isset($result['_type'])) {
                    if (Reservation::getSegmentMap()[0] === $result['_type'] && Reservation::getSegmentMap()[1] === $_type) {
                        $result['last'] = $data[$i];

                        return $result;
                    } elseif (Rental::getSegmentMap()[0] === $result['_type'] && Rental::getSegmentMap()[1] === $this->getType($data[$i]['id'])) {
                        $result['last'] = $data[$i];

                        return $result;
                    }
                }
            }
        }

        if (!isset($result['last']) && isset($result['first'])) {
            $result['last'] = $result['first'];
        }

        return $result;
    }

    private function getNextPlan($data): array
    {
        $result = [];

        for ($i = 0, $iCount = \count($data); $i < $iCount; $i++) {
            if ('planStart' === $data[$i]['type'] && time() < $data[$i]['startDate']) {
                $result = $data[$i];
            } elseif (!empty($result)) {
                if ('segment' === $data[$i]['type'] && !array_key_exists('first', $result)) {
                    $result['first'] = $data[$i];
                } elseif ('planEnd' === $data[$i]['type'] && array_key_exists('first', $result)) {
                    $result['last'] = $data[$i - 1];

                    return $result;
                }
            }
        }

        return [];
    }

    private function isStoping(array $data): bool
    {
        if (!isset($data[1])) {
            return true;
        }
        $stop = $prev = $data[0];
        $data = array_slice($data, 1);

        foreach ($data as $next) {
            if ($next['startDate'] - $prev['endDate'] > 86400) {
                return true;
            }

            if ('segment' !== $next['type']) {
                $prev = $next;

                continue;
            }

            if ('L' === $this->getType($next['id'])
                && ($next['startDate'] - $prev['endDate']) < 86400
            ) {
                return false;
            }

            break;
        }

        return true;
    }

    private function strReplaceTag(string $subject, string $replace): string
    {
        return preg_replace('#<[^>]+>#', $replace, $subject);
    }

    private function findColumnData(string $findKey, array $data)
    {
        $result = null;

        foreach ($data as $key => $item) {
            if (array_key_exists($findKey, $item)) {
                return $item[$findKey];
            }
        }

        return $result;
    }

    private function detectNextSegmentNotTransfer($data)
    {
        if (!isset($data[0]) || $this->getType($data[0]['id']) !== Trip::getSegmentMap()[0]) {
            return true;
        }

        $stop = null;
        $segments = array_filter($data, fn ($item) => 'T' === $this->getType($item['id']));
        $prev = $segments[0];

        foreach ($segments as $next) {
            if (($next['endDate'] - $prev['startDate']) < 86400) {
                $prev = $next;

                continue;
            }

            $stop = $prev;

            break;
        }

        return $stop ?? $prev;
    }
}
