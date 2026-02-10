<?php

namespace AwardWallet\MainBundle\Form\Type\Cart;

use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Transformer\StateTransformer;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BillingAddressType extends AbstractType
{
    protected $request;
    protected $em;
    /** @var Usr user */
    protected $user;
    protected $translator;
    /** @var GeoLocation */
    private $geoLocation;

    public function __construct(RequestStack $requestStack, EntityManager $em, TokenStorageInterface $tokenStorage, TranslatorInterface $translator, GeoLocation $geoLocation)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->em = $em;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->translator = $translator;
        $this->geoLocation = $geoLocation;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('addressname', null, [
//                'label' => /** @Desc("Address Nick") */ 'address.nick',
//                'attr' => [
//                    'notice' => $this->translator->trans( /** @Desc("i.e. 'Home' or 'Work'") */ 'address.nick.notice')
//                ]
//            ])
            ->add('firstname', null, [
                'label' => 'login.first',
            ])
            ->add('lastname', null, [
                'label' => 'login.name',
            ])
            ->add('address1', null, [
                'label' => /** @Desc("Street address 1") */ 'street.address.1',
            ])
            ->add('address2', null, [
                'label' => /** @Desc("Street address 2") */ 'street.address.2',
            ])
            ->add('city', null, [
                'label' => /** @Desc("City") */ 'city',
                'attr' => [
                    'notice' => (230 === $this->user->getCountryid() || 'US' === $this->user->getRegion() ? $this->translator->trans( /** @Desc("Enter APO, FPO or DPO for APO addresses") */ 'city.notice') : ''), // only for USA
                ],
            ])
            ->add('countryid', null, [
                'label' => 'cart.country',
                'placeholder' => 'account.option.please.select',
            ])
            ->add('stateid', TextType::class, [
                'label' => /** @Desc("State/province") */ 'state.province',
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add('zip', null, [
                'label' => /** @Desc("Zip/postal code") */ 'zip.postal.code',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Billingaddress $address */
            $address = $event->getData();

            if (!$address->getBillingaddressid()) {
                $countryRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Country::class);
                $stateRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\State::class);

                if ($this->user->getCountryid() || $this->user->getStateid() || $this->user->getCity() || $this->user->getZip()) {
                    if ($countryId = $this->user->getCountryid()) {
                        $address->setCountryid($countryRep->find($countryId));
                    }

                    if ($stateId = $this->user->getStateid()) {
                        $address->setStateid($stateRep->find($stateId));
                    }
                    $address->setCity($this->user->getCity());
                    $address->setZip($this->user->getZip());
                } else {
                    $ip = $this->request->getClientIp();
                    $country = $this->geoLocation->getCountryIdByIp($ip);

                    if ($country instanceof Country) {
                        $address->setCountryid($country);
                    }
                }

                //                if ($stateId = $detectedPlace['State']['ID'])
                //                    $address->setStateid($this->em->getRepository(\AwardWallet\MainBundle\Entity\State::class)->find($stateId));

                //                if ($city = $detectedPlace['City']['Name'])
                //                    $address->setCity($city);

                $address->setFirstname($this->user->getFirstname());
                $address->setLastname($this->user->getLastname());
                $address->setAddress1($this->user->getAddress1());
                $address->setAddress2($this->user->getAddress2());
            }
        });

        $builder->get('stateid')->addViewTransformer(new StateTransformer($this->em, $this->request));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\MainBundle\Entity\Billingaddress',
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'billing_address';
    }
}
