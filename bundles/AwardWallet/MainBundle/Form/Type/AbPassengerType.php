<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbPassenger;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Transformer\Entity2IdTransformer;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Annotation\Desc;
use JMS\TranslationBundle\Annotation\Ignore;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbPassengerType extends AbstractType implements TranslationContainerInterface
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    private $translator;

    /** @var \AwardWallet\MainBundle\Globals\Localizer\LocalizeService */
    private $localizer;

    public function __construct(EntityManager $em, TranslatorInterface $translator, LocalizeService $localizer)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->localizer = $localizer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->em;
        $booker = $options['booker'];
        $localizer = $this->localizer;
        $isBooker = $options['user'] && $options['user']->isBusiness() && $options['user']->isBooker();
        /** @var \AwardWallet\MainBundle\Entity\AbBookerInfo $bookerInfo */
        $bookerInfo = $booker->getBookerInfo();

        if ($options['user']) {
            if ($isBooker) {
                $builder->add('Useragent', Select2HiddenType::class, [
                    'label' => 'booking.request.add.form.traveler.search',
                    'required' => false,
                    'configs' => 'f:$.extend({}, AddForm.select2hiddenOptions, Passengers.select2Options)',
                    'init-data' => function ($value) use ($em, $booker, $localizer) {
                        if (!empty($value)) {
                            return $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->getAgentInfo($booker->getUserid(), $value, $localizer);
                        }

                        return null;
                    },
                    'transformer' => new Entity2IdTransformer(
                        $this->em,
                        Useragent::class,
                        'Useragentid'
                    ),
                ]);
                $builder->add('new_member');
                $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                    $form = $event->getForm();
                    $passenger = $event->getData();
                    $data = false;

                    if ($passenger instanceof AbPassenger) {
                        if (!$passenger->getUseragent() && $passenger->getAbPassengerID()) {
                            $data = true;
                        }
                    }
                    $form->add('new_member', CheckboxType::class, [
                        'label' => /** @Ignore */
                            $this->translator->trans('booking.request.add.form.traveler.newmember', ['%booker%' => $options['booker']->getBookerInfo()->getServiceName()], 'booking'),
                        'mapped' => false,
                        'required' => false,
                        'data' => $data,
                    ]);
                });
            } else {
                $builder->add('templates', ChoiceTemplateType::class, [
                    'label' => 'booking.request.add.form.traveler.pattern',
                    'templates' => $this->getPassengerTemplates($options['user']),
                    'js_callback' => 'Passengers.setTemplate',
                    'placeholder' => 'booking.request.add.form.traveler.select',
                ]);
            }
        }
        $builder->add('FirstName', TextType::class, [
            'label' => 'booking.request.add.form.traveler.fname',
            'help' => 'booking.request.add.form.traveler.help',
            'required' => true,
            'attr' => [
                'maxlength' => 30,
            ],
        ]);
        $builder->add('MiddleName', TextType::class, [
            'label' => 'booking.request.add.form.traveler.mname',
            'help' => 'booking.request.add.form.traveler.help',
            'required' => false,
            'attr' => [
                'maxlength' => 30,
            ],
        ]);
        $builder->add('LastName', TextType::class, [
            'label' => 'booking.request.add.form.traveler.lname',
            'help' => 'booking.request.add.form.traveler.help',
            'required' => true,
            'attr' => [
                'maxlength' => 30,
            ],
        ]);
        $builder->add('Birthday', DatePickerType::class, [
            'label' => 'booking.request.add.form.traveler.birthday',
            'help' => 'booking.request.add.form.traveler.birthday-help',
            'required' => !$isBooker,
            'datepicker_options' => [
                'defaultDate' => '+1y',
                'yearRange' => '1910:' . (date('Y') + 1),
            ],
        ]);
        $builder->add('Gender', ChoiceType::class, [
            /** @Desc("Gender") */
            'label' => 'booking.passenger.gender',
            'choices' => [
                /** @Desc("Male") */
                'booking.passenger.gender.male' => 'M',

                /** @Desc("Female") */
                'booking.passenger.gender.female' => 'F',
            ],
            'required' => true,
            'expanded' => true,
            'multiple' => false,
        ]);

        if ($bookerInfo->isUsCentric()) {
            $builder->add('Nationality', TwoChoicesOrTextType::class, [
                'label' => 'booking.request.add.form.traveler.us_citizen',
                'help' => 'booking.request.add.form.traveler.nationality-help',
                'yes_value' => 'US',
                'no_value' => 0,
                'no_label' => $this->translator->trans('no', [], 'booking'),
                'yes_label' => $this->translator->trans('yes', [], 'booking'),
                'text_widget_options' => ['attr' => ['maxlength' => 100]],
                'widget_options' => [
                    'yes_without_help' => true,
                ],
                'required' => true,
            ]);
        } else {
            $builder->add('Nationality',
                TextType::class,
                [
                    'label' => /** @Desc("Traveler Citizenship") */ 'booking.request.add.form.traveler.citizenship',
                    'help' => 'booking.request.add.form.traveler.nationality-help',
                    'attr' => [
                        'maxlength' => 100,
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbPassenger',
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'booking',
            'user' => null,
        ]);
        $resolver->setRequired(['booker']);
        $resolver->setDefined(['user']);
        $resolver->setAllowedTypes('user', ['null', 'AwardWallet\\MainBundle\\Entity\\Usr']);
        $resolver->setAllowedTypes('booker', 'AwardWallet\\MainBundle\\Entity\\Usr');
    }

    public function getBlockPrefix()
    {
        return 'booking_request_passenger';
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            new Message('booking.request.add.form.traveler.newmember', 'booking'),
            new Message('booking.request.add.form.traveler.nationality-help', 'booking'),
            new Message('booking.request.add.form.traveler.birthday-help', 'booking'),
            new Message('booking.request.add.form.traveler.help', 'booking'),
        ];
    }

    private function getPassengerTemplates(Usr $user)
    {
        if (isset($this->cachedTemplates)) {
            return $this->cachedTemplates;
        }

        $this->cachedTemplates = $this->em
            ->getRepository(\AwardWallet\MainBundle\Entity\AbPassenger::class)
            ->getPassengerTemplates($user);
        $this->cachedTemplates = array_merge(
            [$this->translator->trans('booking.request.add.form.traveler.new', [], 'booking') => []],
            $this->cachedTemplates
        );

        return $this->cachedTemplates;
    }
}
