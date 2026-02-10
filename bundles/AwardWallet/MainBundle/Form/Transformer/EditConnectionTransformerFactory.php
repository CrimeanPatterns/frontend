<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\UserConnectionModel;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class EditConnectionTransformerFactory
{
    public function createEditConnectionTransformerWithChoiceType(): DataTransformerInterface
    {
        return $this->createTransformer(function (array $selectedTimelines) {
            return function (object $object) use ($selectedTimelines) {
                $id = SharedTimelinesTransformerFactory::generateId($object);

                yield $id => isset($selectedTimelines[$id]);
            };
        });
    }

    public function createEditConnectionTransformerWithSharingTimelinesType(): DataTransformerInterface
    {
        return $this->createTransformer(function (array $selectedTimelines) {
            return function (object $object, $index) use ($selectedTimelines) {
                $id = SharedTimelinesTransformerFactory::generateId($object);

                yield $index => [$object, isset($selectedTimelines[$id])];
            };
        });
    }

    private function createTransformer(callable $sharedTimelinesMapperProvider): DataTransformerInterface
    {
        return new CallbackTransformer(
            function (/** @var Useragent $useragent */ $useragent) use ($sharedTimelinesMapperProvider) {
                $user = $useragent->getClientid();
                $selectedTimelines =
                    it($useragent->getSharedTimelines()->getValues())
                    ->flatMap(function (TimelineShare $timelineShare) {
                        $fm = $timelineShare->getFamilyMember();

                        yield $fm ? $fm->getUseragentid() : 'my' => true;
                    })
                    ->toArrayWithKeys();

                return (new UserConnectionModel())
                    ->setSharebydefault($useragent->getSharebydefault())
                    ->setAccesslevel($useragent->getAccesslevel())
                    ->setTripsharebydefault($useragent->getTripsharebydefault())
                    ->setTripAccessLevel($useragent->getTripAccessLevel())
                    ->setSharedTimelines(
                        it([$user])
                        ->chain($user->getFamilyMembers())
                        ->values()
                        ->flatMapIndexed($sharedTimelinesMapperProvider($selectedTimelines))
                        ->toArrayWithKeys()
                    )
                    ->setEntity($useragent);
            },
            function ($val) {
                return $val;
            }
        );
    }
}
