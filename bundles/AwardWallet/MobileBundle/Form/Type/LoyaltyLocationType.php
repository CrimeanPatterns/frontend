<?php

namespace AwardWallet\MobileBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\LocationSetting;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\LoyaltyLocationModel;
use AwardWallet\MainBundle\Form\Transformer\LoyaltyLocationTransformer;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\MainBundle\Service\TrackedLocationsLimiter;
use AwardWallet\MobileBundle\Form\View\Block\StoreLocation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoyaltyLocationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoyaltyLocationTransformer
     */
    private $dataTransformer;

    /**
     * @var LoyaltyLocation
     */
    private $loyaltyLocation;
    private TrackedLocationsLimiter $trackedLocationsLimiter;

    public function __construct(
        TranslatorInterface $translator,
        LoyaltyLocationTransformer $transformer,
        LoyaltyLocation $loyaltyLocation,
        TrackedLocationsLimiter $trackedLocationsLimiter
    ) {
        $this->translator = $translator;
        $this->dataTransformer = $transformer;
        $this->loyaltyLocation = $loyaltyLocation;
        $this->trackedLocationsLimiter = $trackedLocationsLimiter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tr = $this->translator;
        $this->dataTransformer->setUser($options['user']);
        $builder->addModelTransformer($this->dataTransformer);
        $builder->setAttribute('submit_label',
            $options['edit'] ? $tr->trans('form.button.update') : $tr->trans('form.button.save')
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->preSetData($event, $options);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LoyaltyLocationModel::class,
            'translation_domain' => 'messages',
            'required' => false,
            'edit' => false,
        ]);
        $resolver->setRequired(['user']);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function getBlockPrefix()
    {
        return 'location';
    }

    protected function preSetData(FormEvent $event, $options)
    {
        /** @var Location $location */
        $location = $event->getData();
        $form = $event->getForm();
        /** @var Usr $user */
        $user = $options['user'];

        $form->add('map', BlockContainerType::class, [
            'blockData' => new StoreLocation(),
        ]);

        if ($options['edit']) {
            $tracked = $this->loyaltyLocation->getCountTracked($options['user']);
            $disabled = $tracked >= $this->trackedLocationsLimiter->getMaxTrackedLocations();

            if ($disabled) {
                $settings = $location->getLocationSettings()->filter(function ($item) use ($user) {
                    /** @var LocationSetting $item */
                    return $user->getUserid() == $item->getUser()->getUserid();
                });

                if ($settings->count() > 0) {
                    /** @var LocationSetting $set */
                    $set = $settings->first();

                    if ($set->isTracked()) {
                        $disabled = false;
                    }
                }
            }

            $form->add('tracked', SwitcherType::class, [
                'label' => 'loyalty-location.track-location',
                'disabled' => $disabled,
                'attr' => ['notice' => "loyalty-location.track-location.notice"],
            ]);
        } else {
            $form->add('tracked', HiddenType::class, [
                'data' => 1,
            ]);
        }

        $form->add('name', HiddenType::class, ['required' => true]);
        $form->add('lat', HiddenType::class, ['required' => true]);
        $form->add('lng', HiddenType::class, ['required' => true]);

        $isStaff = $user->hasRole('ROLE_STAFF');
        $form->add(
            'radius',
            $isStaff ? TextType::class : HiddenType::class,
            $isStaff ? ['label' => /** @Ignore */ 'Radius'] : []
        );
    }
}
