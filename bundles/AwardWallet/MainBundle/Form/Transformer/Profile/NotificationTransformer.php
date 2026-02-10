<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Donotsend;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class NotificationTransformer extends AbstractModelTransformer
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Usr $user
     */
    public function transform($user)
    {
        return (new NotificationModel())
            // email
            ->setEmailDisableAll(!is_null($this->getDoNotSend($user->getEmail())))
            ->setEmailExpire($user->getEmailexpiration())
            ->setEmailRewardsActivity($user->getEmailrewards())
            ->setEmailNewPlans($user->getEmailnewplans())
            ->setEmailPlanChanges($user->getEmailplanschanges())
            ->setEmailCheckins($user->getCheckinreminder())
            ->setEmailBookingMessages($user->isEmailBookingMessages())
            ->setEmailProductUpdates($user->getEmailproductupdates())
            ->setEmailOffers($user->getEmailoffers())
            ->setEmailNewBlogPosts($user->getEmailNewBlogPosts())
            ->setEmailInviteeReg($user->getEmailInviteeReg())
            ->setEmailConnected($user->isEmailConnectedAlert())
            ->setEmailNotConnected($user->isEmailFamilyMemberAlert())
            // wp
            ->setWpDisableAll($user->isWpDisableAll())
            ->setWpExpire($user->isWpExpire())
            ->setWpRewardsActivity($user->isWpRewardsActivity())
            ->setWpNewPlans($user->isWpNewPlans())
            ->setWpPlanChanges($user->isWpPlanChanges())
            ->setWpCheckins($user->isWpCheckins())
            ->setWpBookingMessages($user->isWpBookingMessages())
            ->setWpProductUpdates($user->isWpProductUpdates())
            ->setWpOffers($user->isWpOffers())
            ->setWpNewBlogPosts($user->isWpNewBlogPosts())
            ->setWpInviteeReg($user->isWpInviteeReg())
            ->setWpConnected($user->isWpConnectedAlert())
            ->setWpNotConnected($user->isWpFamilyMemberAlert())
            // mp
            ->setMpDisableAll($user->isMpDisableAll())
            ->setMpExpire($user->isMpExpire())
            ->setMpRewardsActivity($user->isMpRewardsActivity())
            ->setMpNewPlans($user->isMpNewPlans())
            ->setMpPlanChanges($user->isMpPlanChanges())
            ->setMpCheckins($user->isMpCheckins())
            ->setMpBookingMessages($user->isMpBookingMessages())
            ->setMpProductUpdates($user->isMpProductUpdates())
            ->setMpOffers($user->isMpOffers())
            ->setMpNewBlogPosts($user->isMpNewBlogPosts())
            ->setMpInviteeReg($user->isMpInviteeReg())
            ->setMpConnected($user->isMpConnectedAlert())
            ->setMpNotConnected($user->isMpFamilyMemberAlert())
            ->setMpRetailCards($user->isMpRetailCards())
            ->setEntity($user);
    }

    public function transformToEntity(Usr $user, NotificationModel $model, Request $request)
    {
        // email
        if ($model->isEmailDisableAll()) {
            $this->addDoNotSend($user->getEmail(), $request->getClientIp());
        } else {
            $this->removeDoNotSend($user->getEmail());
        }
        $user->setEmailexpiration($model->getEmailExpire());
        $user->setEmailrewards($model->getEmailRewardsActivity());
        $user->setEmailnewplans($model->isEmailNewPlans());
        $user->setEmailplanschanges($model->isEmailPlanChanges());
        $user->setCheckinreminder($model->isEmailCheckins());
        $user->setEmailBookingMessages($model->isEmailBookingMessages());
        $user->setEmailproductupdates($model->isEmailProductUpdates());
        $user->setEmailoffers($model->isEmailOffers());
        $user->setEmailNewBlogPosts($model->getEmailNewBlogPosts());
        $user->setEmailInviteeReg($model->isEmailInviteeReg());
        $user->setEmailConnectedAlert($model->isEmailConnected());
        $user->setEmailFamilyMemberAlert($model->isEmailNotConnected());
        // wp
        $user->setWpDisableAll($model->isWpDisableAll());
        $user->setWpExpire($model->isWpExpire());
        $user->setWpRewardsActivity($model->isWpRewardsActivity());
        $user->setWpNewPlans($model->isWpNewPlans());
        $user->setWpPlanChanges($model->isWpPlanChanges());
        $user->setWpCheckins($model->isWpCheckins());
        $user->setWpBookingMessages($model->isWpBookingMessages());
        $user->setWpProductUpdates($model->isWpProductUpdates());
        $user->setWpOffers($model->isWpOffers());
        $user->setWpNewBlogPosts($model->isWpNewBlogPosts());
        $user->setWpInviteeReg($model->isWpInviteeReg());
        $user->setWpConnectedAlert($model->isWpConnected());
        $user->setWpFamilyMemberAlert($model->isWpNotConnected());
        // mp
        $user->setMpDisableAll($model->isMpDisableAll());
        $user->setMpExpire($model->isMpExpire());
        $user->setMpRewardsActivity($model->isMpRewardsActivity());
        $user->setMpNewPlans($model->isMpNewPlans());
        $user->setMpPlanChanges($model->isMpPlanChanges());
        $user->setMpCheckins($model->isMpCheckins());
        $user->setMpBookingMessages($model->isMpBookingMessages());
        $user->setMpProductUpdates($model->isMpProductUpdates());
        $user->setMpOffers($model->isMpOffers());
        $user->setMpNewBlogPosts($model->isMpNewBlogPosts());
        $user->setMpInviteeReg($model->isMpInviteeReg());
        $user->setMpConnectedAlert($model->isMpConnected());
        $user->setMpFamilyMemberAlert($model->isMpNotConnected());
        $user->setMpRetailCards($model->isMpRetailCards());
    }

    /**
     * @param string $email
     * @return Donotsend|null
     */
    protected function getDoNotSend($email)
    {
        $dns = $this->em
            ->getRepository(\AwardWallet\MainBundle\Entity\Donotsend::class)
            ->findOneByEmail($email);

        return $dns;
    }

    protected function addDoNotSend($email, $ip)
    {
        $dns = $this->getDoNotSend($email);

        if ($dns) {
            return;
        }
        $dns = new Donotsend($email, $ip);
        $this->em->persist($dns);
        $this->em->flush($dns);
    }

    protected function removeDoNotSend($email)
    {
        $dns = $this->getDoNotSend($email);

        if ($dns) {
            $this->em->remove($dns);
            $this->em->flush($dns);
        }
    }
}
