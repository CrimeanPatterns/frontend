<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Useragent;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class BusinessMemberType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManager $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $agentAccessLevelsBooking = [
            $this->translator->trans(/** @Desc("Full control (Administrator)") */ "business.useragent.access.full_control") => ACCESS_ADMIN,
            $this->translator->trans(/** @Desc("Booking Administrator (Manager)") */ "business.useragent.access.booking_manager") => ACCESS_BOOKING_MANAGER,
            $this->translator->trans(/** @Desc("Booking View Only") */ "business.useragent.access.booking_view_only") => ACCESS_BOOKING_VIEW_ONLY,
            $this->translator->trans(/** @Desc("No access (Regular member)") */ "business.useragent.access.regular_member") => ACCESS_NONE,
        ];

        $builder->add('accesslevel', ChoiceType::class, [
            'label' => /** @Desc("Access Level") */ 'business.member.access_level',
            'choices' => $agentAccessLevelsBooking,
            'required' => true,
        ]);

        $builder->add('keepUpgraded', CheckboxType::class, [
            'label' => 'user.keep_upgraded',
            'required' => false,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("If you have this checkbox checked user will be automatically upgraded to Awardwallet Plus.") */ "user.keep_upgraded.notice"),
            ],
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Useragent $userAgent */
            $userAgent = $event->getData();

            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            $changes = $uow->getEntityChangeSet($userAgent);

            if (!isset($changes['accesslevel'])) {
                return;
            }

            $usrRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
            $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

            $business = $userAgent->getClientid();

            $criteria = Criteria::create();
            $criteria->where($criteria->expr()->neq('clientid', $business));
            $criteria->andWhere($criteria->expr()->eq('agentid', $userAgent->getAgentid()));
            $criteria->andWhere($criteria->expr()->in('accesslevel', [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]));

            $isAnotherBusinessAdmin = (bool) $uaRep->matching($criteria)->count();

            if ($changes['accesslevel'][1] != ACCESS_NONE && $isAnotherBusinessAdmin) {
                $error = $this->translator->trans(
                    /** @Desc("This user is a business administrator of another account, the user can not administer more than one business account") */ "business.useragent.access.full_control.unique_error"
                );
            } elseif ($changes['accesslevel'][0] == ACCESS_ADMIN && count($usrRep->getBusinessAdmins($business)) < 2) {
                $error = $this->translator->trans(
                    /** @Desc("You are the last Administrator of this business account. You can not remove yourself from Administrators.") */ "business.useragent.access.full_control.last_admin_error"
                );
            }

            if (isset($error)) {
                $event->getForm()->addError(new FormError($error));
            }
        });
    }

    public function getBlockPrefix()
    {
        return 'business_member';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\Useragent',
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
            'validation_groups' => ['Default'],
        ]);
    }
}
