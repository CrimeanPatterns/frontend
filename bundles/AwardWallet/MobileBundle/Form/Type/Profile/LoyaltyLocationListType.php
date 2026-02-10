<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\MainBundle\Service\TrackedLocationsLimiter;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\Type\SwitcherType;
use AwardWallet\MobileBundle\Form\View\Block\GroupTitle;
use AwardWallet\MobileBundle\Form\View\Block\TextProperty;
use AwardWallet\MobileBundle\Form\View\Block\Warning;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoyaltyLocationListType extends AbstractType implements TranslationContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoyaltyLocation
     */
    private $loyaltyLocation;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;
    /**
     * @var MobileExtensionLoader
     */
    private $mobileExtensionLoader;
    private TrackedLocationsLimiter $trackedLocationsLimiter;

    public function __construct(
        TranslatorInterface $translator,
        LoyaltyLocation $loyaltyLocation,
        TrackedLocationsLimiter $trackedLocationsLimiter,
        UrlGeneratorInterface $router,
        MobileExtensionLoader $mobileExtensionLoader
    ) {
        $this->translator = $translator;
        $this->loyaltyLocation = $loyaltyLocation;
        $this->router = $router;
        $this->mobileExtensionLoader = $mobileExtensionLoader;
        $this->trackedLocationsLimiter = $trackedLocationsLimiter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Usr $user */
        $user = $options['user'];
        $tr = $this->translator;
        $tracked = $this->loyaltyLocation->getCountTracked($user);

        $builder->setAttribute('submit_label', false);
        $this->mobileExtensionLoader->loadExtensionByPath($builder, 'mobile/scripts/controllers/profile/LocationsListExtension.js');

        $disabled = false;

        if ($tracked >= $this->trackedLocationsLimiter->getMaxTrackedLocations()) {
            $disabled = true;
            $builder->add('warning', BlockContainerType::class, [
                'blockData' => new Warning($tr->trans("favorite_locations.only-able-up-to", [
                    "%max%" => $this->trackedLocationsLimiter->getMaxTrackedLocations(),
                    "%count%" => $tracked,
                ], 'mobile')),
            ]);
        }

        $builder->add('title', BlockContainerType::class, [
            'blockData' => (new GroupTitle($tr->trans('favorite_locations'))),
        ]);

        $showDisableAll = false;

        foreach ($this->loyaltyLocation->getLocations($user) as $row) {
            $showDisableAll = true;
            $accountId = "a";
            $subAccountId = null;

            if (in_array($row['AccountType'], ['Coupon', 'CouponShare'])) {
                $accountId = "c";
            }
            $accountId .= $row['AccountID'];

            if (in_array($row['AccountType'], ['SubAccount', 'SubAccountShare'])) {
                $subAccountId = $row['SubAccountID'];
            }

            $builder->add('location_' . $row['LocationID'], SwitcherType::class, [
                'label' => $row['ProgramName'],
                'data' => $row['Tracked'] != "0",
                'disabled' => $options['showDisabled'] && $disabled && $row['Tracked'] == "0",
                'attr' => [
                    'class' => 'location',
                    'notice' => $row['LocationName'],
                    'route' => ['name' => 'index.accounts.store-location.edit', 'params' => array_filter([
                        'locationId' => $row['LocationID'],
                        'accountId' => $accountId,
                        'subId' => $subAccountId,
                    ])],
                ],
            ]);
        }

        if ($showDisableAll) {
            $builder->add('disableAll', BlockContainerType::class, [
                'blockData' => (new TextProperty($tr->trans('disable_all_locations')))
                        ->setFormLink($this->router->generate('aw_mobile_notifications', ['group' => 'push'])),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'messages',
            'showDisabled' => true,
            'required' => false,
        ]);
        $resolver->setRequired(['user']);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function getBlockPrefix()
    {
        return 'location_list';
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('favorite_locations'))->setDesc('Favorite Locations'),
            (new Message('disable_all_locations'))->setDesc('Disable All Retail Cards Notifications'),
            (new Message('favorite_locations.only-able-up-to', 'mobile'))
                ->setDesc('Currently we are only able to geographically monitor up to %max% store locations for you and you already have %count% store locations monitored. Below, you can pick which %max% store locations you wish to track geographically, to do that please deselect the ones you don\'t need to monitor.'),
        ];
    }
}
