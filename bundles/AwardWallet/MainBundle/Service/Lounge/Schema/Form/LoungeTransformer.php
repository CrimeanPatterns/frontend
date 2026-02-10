<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema\Form;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Lounge\Action\FreezeAction;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;

class LoungeTransformer extends AbstractModelTransformer
{
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Lounge $value
     * @return LoungeModel
     */
    public function transform($value)
    {
        $oh = $value->getOpeningHours();
        $ohString = null;

        if ($oh instanceof StructuredOpeningHours) {
            $ohString = json_encode($oh->getData());
        } elseif ($oh instanceof RawOpeningHours) {
            $ohString = $oh->getRaw();
        }

        $actions = $value->getActions();
        $freezeAction = null;

        foreach ($actions as $loungeAction) {
            $action = $loungeAction->getAction();

            if ($action instanceof FreezeAction) {
                $freezeAction = (new FreezeActionModel())
                    ->setProps($action->getProps())
                    ->setEmails(implode(', ', $action->getEmails()))
                    ->setDeleteDate($loungeAction->getDeleteDate())
                    ->setEntity($loungeAction);

                break;
            }
        }

        if (is_null($freezeAction)) {
            $freezeAction = (new FreezeActionModel())
                ->setProps([])
                ->setEmails($this->tokenStorage->getToken()->getUser()->getEmail())
                ->setDeleteDate(new \DateTime('+2 week'));
        }

        return (new LoungeModel())
            ->setEntity($value)
            ->setId($value->getId())
            ->setName($value->getName())
            ->setAirportCode($value->getAirportCode())
            ->setTerminal($value->getTerminal())
            ->setGate($value->getGate())
            ->setGate2($value->getGate2())
            ->setOpeningHours($ohString)
            ->setIsRawOpeningHours($oh instanceof RawOpeningHours)
            ->setIsAvailable($value->isAvailable())
            ->setPriorityPassAccess($value->isPriorityPassAccess())
            ->setAmexPlatinumAccess($value->isAmexPlatinumAccess())
            ->setDragonPassAccess($value->isDragonPassAccess())
            ->setLoungeKeyAccess($value->isLoungeKeyAccess())
            ->setLocation($value->getLocation())
            ->setLocationParaphrased($value->getLocationParaphrased())
            ->setAdditionalInfo($value->getAdditionalInfo())
            ->setAmenities($value->getAmenities())
            ->setRules($value->getRules())
            ->setIsRestaurant($value->isRestaurant())
            ->setSources($value->getSources())
            ->setCreateDate($value->getCreateDate())
            ->setUpdateDate(null)
            ->setCheckedBy($value->getCheckedBy() ? $value->getCheckedBy()->getUsername() : null)
            ->setCheckedDate($value->getCheckedDate())
            ->setVisible($value->isVisible())
            ->setAirlines($value->getAirlines())
            ->setAlliances($value->getAlliances())
            ->setFreezeAction($freezeAction);
    }
}
