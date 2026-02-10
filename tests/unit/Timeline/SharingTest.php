<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\AbstractItem;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\TimelineView;
use Codeception\Module\Aw;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

/**
 * @group frontend-unit
 */
class SharingTest extends BaseTimelineTest
{
    public function testSharedCache()
    {
        $provider = $this->container->get('doctrine')->getRepository(Provider::class)->find(Aw::TEST_PROVIDER_ID);
        // prepare my timeline
        $confFields = ['ConfNo' => 'future.trip', 'LastName' => 'Smith'];
        $conf = $this->aw->retrieveByConfNo($this->user, null, $provider, $confFields);
        $this->assertNull($conf);

        $options = $this->getDefaultDesktopQueryOptions()->setUser($this->user);
        $items = $this->manager->query($options);
        $this->assertEquals(2, count($items));

        // prepare jessica timeline
        $jessicaId = $this->aw->createAwUser('jessica' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [], true /* staff user has access to test provider */);
        $this->assertNotNull($jessica = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($jessicaId));
        $this->container->get("aw.manager.user_manager")->loadToken($jessica, false);

        $confFields = ['ConfNo' => 'future.trip.round', 'LastName' => 'Smith'];
        $conf = $this->aw->retrieveByConfNo($jessica, null, $provider, $confFields);
        $this->assertNull($conf);

        $options = $this->getDefaultDesktopQueryOptions()->setUser($jessica);
        $items = $this->manager->query($options);
        $this->assertEquals(3, count($items));

        // create connection between us and check my access to jessica timeline
        $this->container->get("aw.manager.user_manager")->loadToken($this->user, false);
        $userAgent = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($this->aw->createConnection($jessicaId, $this->user->getUserid()));
        $timelineShare = $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class)->addTimelineShare($userAgent);
        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setUserAgent($userAgent)->setWithDetails(true));
        $this->assertEquals(3, count($items));
        $this->assertArrayHasKey('refreshLink', $items[1]['details']);

        $totals = $this->manager->getTotals($this->user);
        $this->assertEquals(1, $totals[$userAgent->getUseragentid()]['count']);

        // change connection level and check that cache was reset
        // this call to check that we will not cache jessica timeline with our
        //		$items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setWithDetails(true));
        //		$this->assertEquals(2, count($items));

        $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class)->removeTimelineShare($userAgent);
        $totals = $this->manager->getTotals($this->user);
        $this->assertCount(1, $totals);

        $timelineShare = $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class)->addTimelineShare($userAgent);
        $totals = $this->manager->getTotals($this->user);
        $this->assertCount(2, $totals);
        //		$this->assertArrayNotHasKey('refreshLink', $items[1]['details']);
    }

    /**
     * @dataProvider mobileSharing
     */
    public function testMobile($shareMap)
    {
        global $kernel;
        // TODO: use DI for the greater good
        $kernel = new \AppKernel('test', true);
        $this->container->get('translator_hijacker')->setContext('mobile');
        $helper = $this->container->get('aw.timeline.helper.mobile');

        /** @var array<UserLabel, UserData> $socialMap */
        $socialMap = [];
        /** @var array<UserLabel, FamilyMemberId[]> $familyMap */
        $familyMap = [];
        /** @var array<DonorId, AcceptorId> $connectionMap */
        $connectionMap = [];

        // $from shares timeline with $toUser
        foreach ($shareMap['links'] as $shareData) {
            [$fromData, $toUserLabel] = $shareData;
            $isApproved = ($shareData[2] ?? 'approved') === 'approved';
            $familyNameLabel = null;
            $fromUserLabel = $fromData;

            if (is_array($fromData) && count($fromData) == 2) {
                [$fromUserLabel, $familyNameLabel] = $fromData;
            }

            foreach ([$fromUserLabel, $toUserLabel] as $userLabel) {
                // add user if not exists yet
                if (!isset($socialMap[$userLabel])) {
                    $socialMap[$userLabel] = [
                        'id' => $userId = $this->aw->createAwUser($userLabel . 'sharing' . $this->aw->grabRandomString(20 - strlen($userLabel . 'sharing')), Aw::DEFAULT_PASSWORD, [
                            'FirstName' => $userLabel . ' First',
                            'LastName' => $userLabel . ' Last',
                        ]),
                        'entity' => $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId),
                    ];

                    $this->db->haveInDatabase('Reservation', [
                        'HotelName' => "{$userLabel} First {$userLabel} Last",
                        'CheckInDate' => (new \DateTime('+1 year'))->format("Y-m-d H:i:s"),
                        'CheckOutDate' => (new \DateTime('+1 year 8 day'))->format("Y-m-d H:i:s"),
                        'UserID' => $socialMap[$userLabel]['id'],
                        'CreateDate' => (new \DateTime())->format("Y-m-d H:i:s"),
                        'Rooms' => 'a:0:{}',
                    ]);
                }
            }

            $fromUserFamilyId = null;

            // add family member if not exists yet
            if (
                isset($familyNameLabel)
                && !isset($familyMap[$fromUserLabel][$familyNameLabel])
            ) {
                $fromUserFamilyId = $this->aw->createFamilyMember($socialMap[$fromUserLabel]['id'], $familyNameLabel . ' First', $familyNameLabel . ' Last');
                $familyMap[$fromUserLabel][$familyNameLabel] = $fromUserFamilyId;

                $this->db->haveInDatabase('Reservation', [
                    'HotelName' => "{$familyNameLabel} First {$familyNameLabel} Last ({$fromUserLabel} First {$fromUserLabel} Last)",
                    'CheckInDate' => (new \DateTime('+1 year'))->format("Y-m-d H:i:s"),
                    'CheckOutDate' => (new \DateTime('+1 year 8 day'))->format("Y-m-d H:i:s"),
                    'UserID' => $socialMap[$fromUserLabel]['id'],
                    'CreateDate' => (new \DateTime())->format("Y-m-d H:i:s"),
                    'UserAgentID' => $fromUserFamilyId,
                    'Rooms' => 'a:0:{}',
                ]);
            }

            // add connection if it not exists yet
            if (
                !isset($connectionMap[$socialMap[$fromUserLabel]['id']])
                || $connectionMap[$socialMap[$fromUserLabel]['id']] != $socialMap[$toUserLabel]['id']
            ) {
                $this->aw->createConnection($socialMap[$fromUserLabel]['id'], $socialMap[$toUserLabel]['id'], $isApproved);
                $connectionMap[$socialMap[$fromUserLabel]['id']] = $socialMap[$toUserLabel]['id'];
            }

            $this->aw->shareAwTimeline($socialMap[$fromUserLabel]['id'], $fromUserFamilyId, $socialMap[$toUserLabel]['id']);
        }

        foreach ($shareMap['views'] as $viewerLabel => $viewData) {
            $userEntity = $socialMap[$viewerLabel]['entity'];
            $timelines = $helper->getUserTimelines($userEntity);

            // assert timeline order by comparing name
            assertEquals(
                it($viewData)
                ->map(function ($otherPersonView) {
                    return is_array($otherPersonView) ?
                        vsprintf('%2$s First %2$s Last (%1$s First %1$s Last)', $otherPersonView) :
                        sprintf('%1$s First %1$s Last', $otherPersonView);
                })
                ->toArray(),

                it($timelines)
                ->map(function (TimelineView $timelineView) { return $timelineView->getName(); })
                ->toArray(),

                "Views order for '{$viewerLabel}' is invalid"
            );

            foreach ($timelines as $position => $timelineView) {
                $timelinePattern = [
                    'date',
                    'checkin ' . $this->getTimelineOwner($timelineView->getName()),
                    'date',
                    'checkout ' . $this->getTimelineOwner($timelineView->getName()),
                ];

                // check timeline view pattern
                assertEquals(
                    array_map(
                        [$this, 'getOwnedItemType'],
                        $timelineView->items
                    ),
                    $timelinePattern,
                    sprintf('Timeline items for "%s" from view "%s" is invalid', $timelineView->name, $viewerLabel)
                );

                $chunkedTimeline = $helper->getChunkedTimeline(
                    null,
                    new \DateTime('+2 year'),
                    $socialMap[$viewerLabel]['entity'],
                    $timelineView->userAgentId ? $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->find($timelineView->userAgentId) : null
                );

                assertNotNull($chunkedTimeline, sprintf('Timeline chunk for "%s" from view "%s" is invalid', $timelineView->name, $viewerLabel));

                // check chunk load pattern
                assertEquals(
                    array_map(
                        [$this, 'getOwnedItemType'],
                        $chunkedTimeline->items
                    ),
                    $timelinePattern,
                    sprintf('Timeline items for "%s" from view "%s" is invalid', $timelineView->name, $viewerLabel)
                );
            }
        }
    }

    public function mobileSharing()
    {
        return [
            [[
                'links' => [
                    ['Person1' /* shares */ , /* to */ 'Person2'],
                    [['Person1', 'Family1_1'] /* shares */ , /* to */ 'Person2'],
                    [['Person1', 'Family1_2'] /* shares */ , /* to */ 'Person2'],
                    ['Person1' /* shares */ , /* to */ 'Person3'],
                    ['Person2' /* shares */ , /* to */ 'Person1'],
                    [['Person2', 'Family2_1'] /* shares */ , /* to */ 'Person3'],
                    ['Person3' /* shares */ , /* to */ 'Person4', 'unapproved_connection'],
                ],
                'views' => [
                    'Person1' => [
                        'Person1',
                        'Family1_1',
                        'Family1_2',
                        'Person2',
                    ],
                    'Person2' => [
                        'Person2',
                        ['Person1', 'Family1_1'],
                        ['Person1', 'Family1_2'],
                        'Family2_1',
                        'Person1',
                    ],
                    'Person3' => [
                        'Person3',
                        ['Person2', 'Family2_1'],
                        'Person1',
                    ],
                    'Person4' => [
                        'Person4',
                    ],
                ],
            ]],
        ];
    }

    protected function getOwnedItemType(AbstractItem $timelineItem)
    {
        return isset($timelineItem->listView) ?
            $timelineItem->type . ' ' . $this->getTimelineOwner($timelineItem->listView->val) :
            $timelineItem->type;
    }

    protected function getTimelineOwner($text)
    {
        if (strpos($text, '(') !== false) {
            return trim(explode('(', $text)[0]);
        }

        return $text;
    }
}
