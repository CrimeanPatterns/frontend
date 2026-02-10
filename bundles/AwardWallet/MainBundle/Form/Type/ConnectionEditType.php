<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\UserConnectionModel;
use AwardWallet\MainBundle\Form\Transformer\EditConnectionTransformerFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConnectionEditType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var EditConnectionTransformerFactory
     */
    private $editConnectionTransformerFactory;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        EditConnectionTransformerFactory $editConnectionTransformer
    ) {
        $this->translator = $translator;
        $this->em = $em;
        $this->editConnectionTransformerFactory = $editConnectionTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Useragent $userAgent */
        $userAgent = $builder->getData();
        $user = $userAgent->getClientid();
        $agent = $userAgent->getAgentid();
        $agentName = htmlspecialchars($agent->isBusiness() ? $agent->getCompany() : $agent->getFullName());

        $awardSharingByDefault = [
            $this->translator->trans(/** @Desc("Share new accounts that I add with %agentName%") */ "new.accounts.share.with", ['%agentName%' => $agentName]) => 1,
            $this->translator->trans(/** @Desc("Do not share new accounts that I add with %agentName%") */ "new.accounts.do_not.share.with", ['%agentName%' => $agentName]) => 0,
        ];

        $tripSharingByDefault = [
            $this->translator->trans(/** @Desc("Share timelines of newly added members with %agentName%") */ "new.timelines.share.with", ['%agentName%' => $agentName]) => 1,
            $this->translator->trans(/** @Desc("Do not share timelines of newly added members with %agentName%") */ "new.timelines.do_not.share.with", ['%agentName%' => $agentName]) => 0,
        ];

        $allowAgentTo = [
            $this->translator->trans(/** @Desc("Read account numbers / usernames and elite statuses only") */ "account.access.read_number") => ACCESS_READ_NUMBER,
            $this->translator->trans(/** @Desc("Read account balances and elite statuses only") */ "account.access.read_balance_and_status") => ACCESS_READ_BALANCE_AND_STATUS,
            $this->translator->trans(/** @Desc("Read all information excluding passwords") */ "account.access.except_pass") => ACCESS_READ_ALL,
            $this->translator->trans(/** @Desc("Full control (edit, delete, auto-login, view passwords)") */ "account.access.full_control") => ACCESS_WRITE,
        ];

        $tripAccessLevels = [
            $this->translator->trans(/** @Desc("Read travel plans only") */ "timeline.access.read_only") => TRIP_ACCESS_READ_ONLY,
            $this->translator->trans(/** @Desc("Full control (view, edit, delete, move travel plans)") */ "timeline.access.full_control") => TRIP_ACCESS_FULL_CONTROL,
        ];

        $builder->add('sharebydefault', ChoiceType::class, [
            'label' => /** @Desc("By Default") */ 'by.default',
            'choices' => $awardSharingByDefault,
            'required' => true,
            'multiple' => false,
            'expanded' => true,
        ]);

        $builder->add('accesslevel', ChoiceType::class, [
            'label' => $this->translator->trans(/** @Desc("Allow %agentName% to have") */ "allow.agent.to", ['%agentName%' => $agentName]),
            'choices' => $allowAgentTo,
            'required' => true,
            'multiple' => false,
            'expanded' => true,
            'attr' => [
                'notice' => '* ' . $this->translator->trans(/** @Desc("Only applies to the accounts that you are sharing") */ "allow.agent.notice"),
            ],
        ]);

        $builder->add('tripsharebydefault', ChoiceType::class, [
            'label' => /** @Desc("By Default") */ 'by.default',
            'choices' => $tripSharingByDefault,
            'required' => true,
            'multiple' => false,
            'expanded' => true,
        ]);

        $builder->add('tripAccessLevel', ChoiceType::class, [
            'label' => $this->translator->trans(/** @Desc("Allow %agentName% to have") */ "allow.agent.to", ['%agentName%' => $agentName]),
            'choices' => $tripAccessLevels,
            'required' => true,
            'multiple' => false,
            'expanded' => true,
        ]);

        $builder->add('sharedTimelines', SharingTimelinesType::class, ['useragent' => $userAgent]);
        $builder->addModelTransformer($this->editConnectionTransformerFactory->createEditConnectionTransformerWithSharingTimelinesType());
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'connection_edit';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserConnectionModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
            'validation_groups' => ['Default'],
        ]);
    }
}
