<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\UserConnectionModel;
use AwardWallet\MainBundle\Form\Transformer\EditConnectionTransformerFactory;
use AwardWallet\MainBundle\Form\Transformer\SharedTimelinesTransformerFactory;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\View\Block\SubTitle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ConnectionEditType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var EditConnectionTransformerFactory
     */
    private $editConnectionTransformerFactory;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        TranslatorInterface $translator,
        EditConnectionTransformerFactory $editConnectionTransformer,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->translator = $translator;
        $this->editConnectionTransformerFactory = $editConnectionTransformer;
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Useragent $userAgent */
        $userAgent = $builder->getData();
        $user = $userAgent->getClientid();
        $agent = $userAgent->getAgentid();

        $agentName = htmlspecialchars($agent->isBusiness() ? $agent->getCompany() : $agent->getFullName());

        $awardSharingByDefault = [
            $this->translator->trans("new.accounts.share.with", ['%agentName%' => $agentName]) => 1,
            $this->translator->trans("new.accounts.do_not.share.with", ['%agentName%' => $agentName]) => 0,
        ];

        $tripSharingByDefault = [
            $this->translator->trans("new.timelines.share.with", ['%agentName%' => $agentName]) => 1,
            $this->translator->trans("new.timelines.do_not.share.with", ['%agentName%' => $agentName]) => 0,
        ];

        $allowAgentTo = [
            $this->translator->trans("account.access.read_number") => ACCESS_READ_NUMBER,
            $this->translator->trans("account.access.read_balance_and_status") => ACCESS_READ_BALANCE_AND_STATUS,
            $this->translator->trans("account.access.except_pass") => ACCESS_READ_ALL,
            $this->translator->trans("account.access.full_control") => ACCESS_WRITE,
        ];

        $tripAccessLevels = [
            $this->translator->trans("timeline.access.read_only") => TRIP_ACCESS_READ_ONLY,
            $this->translator->trans("timeline.access.full_control") => TRIP_ACCESS_FULL_CONTROL,
        ];

        $builder->add('award_title', BlockContainerType::class, [
            'blockData' => (new SubTitle($this->translator->trans('award.sharing.with', ['%agent_fullname%' => $agent->getFullName()]))),
        ]);

        $builder->add('sharebydefault', ChoiceType::class, [
            'label' => 'by.default',
            'choices' => $awardSharingByDefault,
            'required' => true,
            'multiple' => false,
        ]);

        $builder->add('accesslevel', ChoiceType::class, [
            'label' => $this->translator->trans("allow.agent.to", ['%agentName%' => $agentName]),
            'choices' => $allowAgentTo,
            'required' => true,
            'multiple' => false,
            'attr' => [
                'notice' => '* ' . $this->translator->trans("allow.agent.notice"),
            ],
        ]);

        $builder->add('trip_sharing', BlockContainerType::class, [
            'blockData' => (new SubTitle($this->translator->trans('trip.sharing.with', ['%agent_fullname%' => $agent->getFullName()]))),
        ]);

        $builder->add('tripsharebydefault', ChoiceType::class, [
            'label' => 'by.default',
            'choices' => $tripSharingByDefault,
            'required' => true,
            'multiple' => false,
        ]);

        $builder->add('tripAccessLevel', ChoiceType::class, [
            'label' => $this->translator->trans("allow.agent.to", ['%agentName%' => $agentName]),
            'choices' => $tripAccessLevels,
            'required' => true,
            'multiple' => false,
        ]);

        $builder->add('sharedTimelines', ChoiceType::class, [
            'multiple' => true,
            'label' => /** @Desc("Share the entire timeline of") */ 'share.entire.timeline.of',
            'choices' => it([$user])
                ->chain($user->getFamilyMembers())
                ->flatMap(function (object $object) {
                    yield $object->getFullName() => SharedTimelinesTransformerFactory::generateId($object);
                })
                ->toArrayWithKeys(),
        ]);

        $builder->addModelTransformer($this->editConnectionTransformerFactory->createEditConnectionTransformerWithChoiceType());
    }

    public function getBlockPrefix()
    {
        return 'mobile_connection_edit';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserConnectionModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }
}
