<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\TravelerProfileModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class TravelerProfileTransformer extends AbstractModelTransformer
{
    /**
     * @param Usr $user
     * @return TravelerProfileModel
     */
    public function transform($user)
    {
        $default = [
            'travelerNumber' => null,
            'dateOfBirth' => null,
            'seatPreference' => null,
            'mealPreference' => null,
            'homeAirport' => null,
            'passport' => [
                'name' => null,
                'number' => null,
                'issueDate' => null,
                'country' => null,
                'expirationDate' => null,
            ],
        ];

        $travelerProfile = array_merge($default, $user->getTravelerProfile());

        return (new TravelerProfileModel())
            ->setTravelerNumber($travelerProfile['travelerNumber'])
            ->setDateOfBirth(
                is_array($travelerProfile['dateOfBirth']) ?
                    new \DateTime($travelerProfile['dateOfBirth']['date']) : $travelerProfile['dateOfBirth']
            )
            ->setSeatPreference($travelerProfile['seatPreference'])
            ->setMealPreference($travelerProfile['mealPreference'])
            ->setHomeAirport($travelerProfile['homeAirport'])
            ->setPassportName($travelerProfile['passport']['name'])
            ->setPassportNumber($travelerProfile['passport']['number'])
            ->setPassportIssueDate(
                is_array($travelerProfile['passport']['issueDate']) ?
                    new \DateTime($travelerProfile['passport']['issueDate']['date']) : $travelerProfile['passport']['issueDate']
            )
            ->setPassportCountry($travelerProfile['passport']['country'])
            ->setPassportExpiration(
                is_array($travelerProfile['passport']['expirationDate']) ?
                    new \DateTime($travelerProfile['passport']['expirationDate']['date']) : $travelerProfile['passport']['expirationDate']
            )
            ->setEntity($user);
    }

    /**
     * @param TravelerProfileModel $value
     * @return mixed|void
     */
    public function reverseTransform($value)
    {
        /** @var Usr $user */
        $user = $value->getEntity();

        $user->setTravelerProfile([
            'travelerNumber' => $value->getTravelerNumber(),
            'dateOfBirth' => $value->getDateOfBirth(),
            'seatPreference' => $value->getSeatPreference(),
            'mealPreference' => $value->getMealPreference(),
            'homeAirport' => $value->getHomeAirport(),
            'passport' => [
                'name' => $value->getPassportName(),
                'number' => $value->getPassportNumber(),
                'issueDate' => $value->getPassportIssueDate(),
                'country' => $value->getPassportCountry(),
                'expirationDate' => $value->getPassportExpiration(),
            ],
        ]);
    }
}
