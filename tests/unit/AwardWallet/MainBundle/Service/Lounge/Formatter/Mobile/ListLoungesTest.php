<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\AbstractView;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\LoungeListItemView;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\ViewInflater;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;

/**
 * @group frontend-unit
 */
class ListLoungesTest extends AbstractViewInflaterTest
{
    public function test247OpenHours()
    {
        $lounge = new Lounge();
        $lounge->setName('Test Lounge');
        $lounge->setAirportCode(self::DEP_CODE);
        $lounge->setIsAvailable(true);
        $lounge->setVisible(true);
        $lounge->setOpeningHours(new StructuredOpeningHours('America/New_York', [
            'monday' => ['00:00-23:59'],
            'tuesday' => ['00:00-23:59'],
            'wednesday' => ['00:00-23:59'],
            'thursday' => ['00:00-23:59'],
            'friday' => ['00:00-23:59'],
            'saturday' => ['00:00-23:59'],
            'sunday' => ['00:00-23:59'],
        ]));
        $this->em->persist($lounge);
        $this->em->flush();

        $data = $this->viewInflater->listLounges(
            $this->user,
            $this->createTripSegment(),
            ViewInflater::STAGE_DEP,
            null,
            null,
            date('Y-m-d 22:00:00')
        );

        /** @var LoungeListItemView[] $items */
        $items = array_filter($data->blocks, fn (AbstractView $view) => $view instanceof LoungeListItemView);
        $this->assertCount(1, $items);
        $item = current($items);
        $this->assertNull($item->nextEventTs);
    }
}
