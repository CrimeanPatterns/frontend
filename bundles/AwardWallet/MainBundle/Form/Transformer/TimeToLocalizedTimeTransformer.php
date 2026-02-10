<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TimeToLocalizedTimeTransformer implements DataTransformerInterface
{
    /**
     * @var LocalizeService
     */
    private $localizeService;

    /**
     * TimeToLocalizedTimeTransformer constructor.
     */
    public function __construct(LocalizeService $localizeService)
    {
        $this->localizeService = $localizeService;
    }

    public function transform($time)
    {
        if (empty($time)) {
            return $time;
        }

        try {
            $dateTime = new \DateTime($time);
        } catch (\Exception $e) {
            return new TransformationFailedException("Unknown time format");
        }

        return $this->localizeService->formatTime($dateTime);
    }

    public function reverseTransform($localizedTime)
    {
        if (empty($localizedTime)) {
            return $localizedTime;
        }

        if (false !== ($parsedTime = $this->parse24HourFormat($localizedTime))) {
            return $parsedTime->format('H:i');
        }

        try {
            $parsedTime = $this->localizeService->parseTime($localizedTime);
        } catch (\RuntimeException $e) {
            throw new TransformationFailedException("Unknown time format", 0, $e);
        }

        return $parsedTime->format('H:i');
    }

    private function parse24HourFormat(string $time)
    {
        if (!preg_match("/^(?'hours'\d{1,2}):(?'minutes'\d{1,2})(?>:(?'seconds'\d{1,2}))?$/", $time, $matches)) {
            return false;
        }
        $hours = $matches['hours'];
        $minutes = $matches['minutes'];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return false;
        }

        return new \DateTime($time);
    }
}
