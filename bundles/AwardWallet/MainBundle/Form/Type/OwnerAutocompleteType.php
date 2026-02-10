<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\TimelineShareRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Transformer\OwnerToUserAgentTransformer;
use AwardWallet\MainBundle\Form\Transformer\UserAgentToIdTransformer;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Voter\UserAgentVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OwnerAutocompleteType extends AbstractType
{
    /**
     * @var UseragentRepository
     */
    private $useragentRepository;

    /**
     * @var TimelineShareRepository
     */
    private $timelineShareRepository;

    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserAgentVoter
     */
    private $userAgentVoter;

    // private EntityManagerInterface $entityManager;

    /**
     * OwnerAutocompleteType constructor.
     */
    public function __construct(
        ContainerInterface $container,
        AwTokenStorageInterface $tokenStorage,
        UserAgentVoter $userAgentVoter,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->useragentRepository = $entityManager->getRepository(Useragent::class);
        $this->timelineShareRepository = $entityManager->getRepository(TimelineShare::class);
        $this->userAgentVoter = $userAgentVoter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DataTransformerChain([
            new OwnerToUserAgentTransformer($this->tokenStorage),
            new UserAgentToIdTransformer($this->useragentRepository),
        ]));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $constraintsCallback = function (Options $options) {
            $user = $this->tokenStorage->getBusinessUser();
            $designation = $options['designation'];

            $canEditTimelineConstraint = function (Owner $owner, ExecutionContextInterface $context) use ($user) {
                if ($user === $owner->getUser()) {
                    return;
                }
                $userAgent = $owner->isFamilyMember() ? $owner->getFamilyMember() : $user->getConnectionWith($owner->getUser());

                if (!$this->userAgentVoter->editTimeline($this->tokenStorage->getToken(), $userAgent)) {
                    $context
                        ->buildViolation(/** @Desc("Please select existing owner.") */ 'select.existing.owner')
                        ->setTranslationDomain('validators')
                        ->addViolation();

                    return;
                }
            };
            $canEditAccountsConstraint = function (Owner $owner, ExecutionContextInterface $context) use ($user) {
                if ($user === $owner->getUser()) {
                    return;
                }
                $userAgent = $owner->isFamilyMember() ? $owner->getFamilyMember() : $user->getConnectionWith($owner->getUser());

                if (!$this->userAgentVoter->editAccounts($this->tokenStorage->getToken(), $userAgent)) {
                    $context
                        ->buildViolation(/** @Desc("Please select existing owner.") */ 'select.existing.owner')
                        ->setTranslationDomain('validators')
                        ->addViolation();

                    return;
                }
            };

            switch ($designation) {
                case OwnerRepository::FOR_ITINERARY_ASSIGNMENT:
                    return [new Constraints\Callback(['callback' => $canEditTimelineConstraint])];

                case OwnerRepository::FOR_ACCOUNT_ASSIGNMENT:
                    return [new Constraints\Callback(['callback' => $canEditAccountsConstraint])];
            }
        };
        $resolver->setDefaults([
            'label' => 'itineraries.timeline.owner',
            'translation_domain' => 'trips',
            'route' => function (Options $options) {
                switch ($options['designation']) {
                    case OwnerRepository::FOR_ITINERARY_ASSIGNMENT:
                        return 'aw_business_members_dropdown_timeline';

                    case OwnerRepository::FOR_ACCOUNT_ASSIGNMENT:
                        return 'aw_business_members_dropdown_accounts';
                }
            },
            'attr' => [
                'class' => 'js-useragent-autocomplete',
            ],
            'placeholder' => function (Options $options) {
                return $options['default_owner']->getFullName();
            },
            'default_owner' => OwnerRepository::getOwner($this->tokenStorage->getBusinessUser()),
            'invalid_message' => /** @Desc("Please select existing owner.") */ 'select.existing.owner',
            'constraints' => $constraintsCallback,
        ]);
        $resolver->setRequired(['designation']);
    }

    public function getParent()
    {
        return AutocompleteType::class;
    }
}
