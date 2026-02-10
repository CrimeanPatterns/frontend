<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Validator;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\DateSequenceInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateSequenceValidator extends ConstraintValidator
{
    private GoogleGeo $geoCoder;

    public function __construct(GoogleGeo $geoCoder)
    {
        $this->geoCoder = $geoCoder;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof DateSequence) {
            throw new UnexpectedTypeException($constraint, DateSequence::class);
        }

        if (!$value instanceof DateSequenceInterface) {
            throw new UnexpectedValueException($constraint, DateSequenceInterface::class);
        }

        if (!is_null($value->getStartDate()) && !is_null($value->getEndDate())) {
            if ($value->getStartLocation() instanceof Aircode) {
                $startLocation = $value->getStartLocation()->getAircode();
                $startType = GEOTAG_TYPE_AIRPORT;
            } else {
                $startLocation = $value->getStartLocation();
                $startType = 0;
            }

            if ($value->getEndLocation() instanceof Aircode) {
                $endLocation = $value->getEndLocation()->getAircode();
                $endType = GEOTAG_TYPE_AIRPORT;
            } else {
                $endLocation = $value->getEndLocation();
                $endType = 0;
            }
            $startDate = Geotag::getLocalDateTimeByGeoTag(
                $value->getStartDate(),
                $startLocation ? $this->geoCoder->findGeoTagEntity($startLocation, null, $startType) : null
            );
            $endDate = Geotag::getLocalDateTimeByGeoTag(
                $value->getEndDate(),
                $endLocation ? $this->geoCoder->findGeoTagEntity($endLocation, null, $endType) : null
            );

            if ($startDate->getTimestamp() > $endDate->getTimestamp()) {
                $this->context
                    ->buildViolation($value->getDateSequenceViolationMessage())
                    ->setTranslationDomain('validators')
                    ->atPath($value->getDateSequenceViolationPath())
                    ->addViolation();
            }
        }
    }
}
