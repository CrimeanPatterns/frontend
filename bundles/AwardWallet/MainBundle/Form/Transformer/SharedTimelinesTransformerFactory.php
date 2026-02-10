<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SharedTimelinesTransformerFactory
{
    public function createTransformer(): DataTransformerInterface
    {
        $choices = [];

        return new CallbackTransformer(
            function (/** @var Usr[]|Useragent[] $timelines */ $timelines) use (&$choices) {
                $choices =
                    it($timelines)
                    ->reindex(function (array $data) {
                        [$timeline, $enabled] = $data;

                        return self::generateId($timeline);
                    })
                    ->toArrayWithKeys();

                return
                    it($choices)
                    ->column(1)
                    ->toArrayWithKeys();
            },
            function ($userValues) use (&$choices) {
                return
                    it($userValues)
                    ->flatMapIndexed(function ($value, $index) use (&$choices) {
                        yield $index => [$choices[$index][0], $value];
                    })
                    ->toArrayWithKeys();
            }
        );
    }

    /**
     * @param Usr|Useragent $object
     */
    public static function generateId(object $object): string
    {
        if ($object instanceof Usr) {
            return 'my';
        } else {
            return $object->getUseragentid();
        }
    }
}
