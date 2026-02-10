<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class OwnerChoiceType extends AbstractType
{
    private Usr $currentUser;

    private OwnerRepository $ownerRepository;

    private TranslatorInterface $translator;

    /**
     * OwnerEntityType constructor.
     */
    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        OwnerRepository $ownerRepository,
        TranslatorInterface $translator
    ) {
        $this->currentUser = $tokenStorage->getBusinessUser();
        $this->ownerRepository = $ownerRepository;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Owner $data */
            $data = $event->getData();
            $form = $event->getForm();
            $options = $form->getConfig()->getOptions();
            $choices = it($options['choices'])->reindex(fn (Owner $owner) => $owner->getIdentityString())->toArrayWithKeys();

            if (!$data || isset($choices[$data->getIdentityString()]) || $form->isSubmitted()) {
                return;
            }

            array_unshift($options['choices'], $data);
            $form->getParent()->add($form->getName(), self::class, $options);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'itineraries.timeline.owner',
            'translation_domain' => 'trips',
            'choices' => function (Options $options) {
                return $this->getChoices($options);
            },
            'choice_label' => function (Owner $owner) {
                if ($owner->isFamilyMember()) {
                    return "{$owner->getFullName()} ({$owner->getUser()->getFullName()})";
                }

                return $owner->getFullName();
            },
            'preferred_choices' => function (Owner $owner) {
                return $owner->isFamilyMemberOfUser($this->currentUser);
            },
            'empty_data' => OwnerRepository::getOwner($this->currentUser),
            'placeholder' => false,
            'attr' => [
                'class' => 'js-useragent-select',
                'notice' => $this->translator->trans('account.notice.choose.loyalty.program'),
            ],
            'choice_value' => function (Owner $owner) {
                return $owner->getIdentityString();
            },
            'choice_attr' => function (Owner $owner) {
                return [
                    'data-email' => $owner->getItineraryForwardingEmail(),
                    'data-shareable' => $owner->isFamilyMemberOfUser($this->currentUser) ? 'shareable' : false,
                ];
            },
        ]);
        $resolver->setRequired(['designation']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // Quote from docs:
        //     Starting from Symfony 4.4, the preferred choices are displayed both at the top
        //     of the list and at their original locations on the list. In prior Symfony versions,
        //     they were only displayed at the top of the list.
        //     ^^^^^ revert that for owner type ^^^^^
        $duplicateOwnerIds = \array_keys(array_intersect_key(
            $this->createChoicesOwnerIdsToArrayKeysMap($view->vars['preferred_choices'] ?? []),
            $choicesIndexesMap = $this->createChoicesOwnerIdsToArrayKeysMap($view->vars['choices'] ?? [])
        ));

        foreach ($duplicateOwnerIds as $ownerId) {
            unset($view->vars['choices'][$choicesIndexesMap[$ownerId]]);
        }
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @param ChoiceView[] $choices
     */
    protected function createChoicesOwnerIdsToArrayKeysMap(array $choices): array
    {
        return
            it($choices)
            ->flip()
            ->mapKeys(function (ChoiceView $choiceView) { return $choiceView->data->getIdentityString(); })
            ->toArrayWithKeys();
    }

    /**
     * @return Owner[]
     */
    private function getChoices(Options $options): array
    {
        return it($this->ownerRepository->findAvailableOwners($options['designation'], $this->currentUser, '', 0))
            ->filter(fn (Owner $owner) => !(
                $owner->isFamilyMember()
                && !$owner->isFamilyMemberOfUser($this->currentUser)
                && !$this->isAllowOtherFamilyMember($options)
            ))
            ->toArray();
    }

    private function isAllowOtherFamilyMember(Options $options): bool
    {
        return isset($options['designation']) && in_array($options['designation'], [OwnerRepository::FOR_ITINERARY_ASSIGNMENT]);
    }
}
