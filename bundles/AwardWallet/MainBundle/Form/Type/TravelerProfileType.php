<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\TravelerProfileModel;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TravelerProfileType extends AbstractType implements TranslationContainerInterface
{
    /**
     * @var LocalizeService
     */
    private $localizer;

    /**
     * @var DataTransformerInterface
     */
    private $transformer;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        LocalizeService $localizer,
        DataTransformerInterface $transformer,
        EntityManagerInterface $em
    ) {
        $this->localizer = $localizer;
        $this->transformer = $transformer;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Usr $user */
        $user = $builder->getData();

        parent::buildForm($builder, $options);

        $builder->add('dateOfBirth', DatePickerType::class, [
            'label' => 'traveler_profile.date-of-birth',
            'required' => false,
            'invalid_message' => /** @Desc("Please, enter valid date and time.") */ 'invalid_date_and_time',
            'datepicker_options' => [
                'yearRange' => '1910:' . (date('Y') + 1),
            ],
        ]);

        $builder->add('seatPreference', ChoiceType::class, [
            'choices' => [
                'seat.window' => 'window', 'seat.aisle' => 'aisle',
            ],
            'label' => 'traveler_profile.seat-preference',
            'placeholder' => false,
            'required' => false,
        ]);

        $builder->add('mealPreference', TextType::class, [
            'label' => 'traveler_profile.meal-preference',
            'required' => false,
        ]);

        $builder->add('homeAirport', TextType::class, [
            'label' => 'traveler_profile.home-airport',
            'required' => false,
        ]);

        $builder->addModelTransformer($this->transformer);
    }

    public function getBlockPrefix()
    {
        return 'traveler_profile';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TravelerProfileModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('traveler_profile.number'))->setDesc('Trusted Traveler Number'),
            (new Message('traveler_profile.date-of-birth'))->setDesc('Date of Birth'),
            (new Message('traveler_profile.seat-preference'))->setDesc('Seat preference'),
            (new Message('traveler_profile.meal-preference'))->setDesc('Meal preference'),
            (new Message('traveler_profile.home-airport'))->setDesc('Home Airport'),
            (new Message('traveler_profile.passport'))->setDesc('Passport'),
            (new Message('traveler_profile.passport-name'))->setDesc('Name'),
            (new Message('traveler_profile.passport-number'))->setDesc('Passport Number'),
            (new Message('traveler_profile.passport-issueDate'))->setDesc('Date of Issue'),
            (new Message('traveler_profile.passport-country'))->setDesc('Country of Issue'),
            (new Message('traveler_profile.passport-expiration'))->setDesc('Expiration'),
        ];
    }
}
