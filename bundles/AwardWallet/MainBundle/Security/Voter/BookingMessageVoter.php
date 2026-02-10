<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BookingMessageVoter extends AbstractVoter
{
    /**
     * @param AbMessage $object
     * @return bool
     */
    public function edit(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        if (!$user instanceof Usr) {
            return false;
        }

        $isEditable = in_array($object->getType(), [AbMessage::TYPE_INTERNAL, AbMessage::TYPE_COMMON])
            && !$object->isInvoice()
            && $object->getAbMessageID();

        if ($object->getFromBooker()) {
            $booker = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($user);

            if ($booker != null && $object->getRequest()->getBooker() == $booker) {
                $isEditable = $isEditable && true;
            } else {
                $isEditable = false;
            }
        } else {
            $isEditable = $isEditable && $object->getUser() == $user;
        }

        if ($this->isBusiness() && $isEditable) {
            $isEditable = $this->container->get(BusinessVoter::class)->businessAccounts($token);
        }

        return $isEditable;
    }

    /**
     * @param AbMessage $object
     * @return bool
     */
    public function delete(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        if (!$user instanceof Usr) {
            return false;
        }

        $canDelete = in_array($object->getType(), [AbMessage::TYPE_INTERNAL, AbMessage::TYPE_COMMON, AbMessage::TYPE_SEAT_ASSIGNMENTS])
            && ($object->getInvoice() === null || !$object->getInvoice()->isPaid())
            && $object->getAbMessageID();

        if ($object->getFromBooker()) {
            /** @var Usr $booker */
            $booker = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBookerByUser($user);

            if ($booker === null || $object->getRequest()->getBooker()->getUserid() !== $booker->getUserid()) {
                $canDelete = false;
            }
        } else {
            $canDelete = $canDelete && $object->getUser() == $user;
        }

        if ($this->isBusiness() && $canDelete) {
            $canDelete = $this->container->get(BusinessVoter::class)->businessAccounts($token);
        }

        return $canDelete;
    }

    protected function getAttributes()
    {
        return [
            'EDIT' => [$this, 'edit'],
            'DELETE' => [$this, 'delete'],
        ];
    }

    protected function getClass()
    {
        return 'AwardWallet\\MainBundle\\Entity\\AbMessage';
    }
}
