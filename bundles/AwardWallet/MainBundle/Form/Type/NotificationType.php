<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationType extends AbstractType implements TranslationContainerInterface
{
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    private TranslatorInterface $translator;

    public function __construct(DataTransformerInterface $transformer, TranslatorInterface $translator)
    {
        $this->dataTransformer = $transformer;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->dataTransformer);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->preSetData($event, $options);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'isBusiness' => false,
            'freeVersion' => false,
            'translation_domain' => 'messages',
            'data_class' => NotificationModel::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'desktop_notification';
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('notification.group.lp', 'mobile'))->setDesc('Loyalty Accounts'),
            (new Message('notification.group.booking', 'mobile'))->setDesc('Booking'),
            (new Message('notification.group.travel', 'mobile'))->setDesc('Travel'),
            (new Message('notification.group.security', 'mobile'))->setDesc('Security'),
            (new Message('notification.group.other', 'mobile'))->setDesc('Other'),

            (new Message('notification.channel.balance_expiration', 'mobile'))->setDesc('Account Balance Expiration'),
            (new Message('notification.channel.balance_expiration.desc', 'mobile'))->setDesc('This channel is used to deliver your loyalty account expiration reminders.'),
            (new Message('notification.channel.rewards_activity', 'mobile'))->setDesc('Rewards Activity'),
            (new Message('notification.channel.rewards_activity.desc', 'mobile'))->setDesc('This channel is used to deliver changes in your loyalty account balances.'),
            (new Message('notification.channel.booking_activity', 'mobile'))->setDesc('Booking Activity'),
            (new Message('notification.channel.booking_activity.desc', 'mobile'))->setDesc('This channel is used to deliver messages related to your award booking requests that are sent by your booking agents.'),
            (new Message('notification.channel.otc', 'mobile'))->setDesc('One-time Codes'),
            (new Message('notification.channel.otc.desc', 'mobile'))->setDesc('This channel is used to deliver your AwardWallet one-time codes in case if we detect suspicious type of a login. You will need this code to login to your AwardWallet profile.'),
            (new Message('notification.channel.checkin', 'mobile'))->setDesc('Flight Check-in'),
            (new Message('notification.channel.checkin.desc', 'mobile'))->setDesc('This channel is used to deliver flight checking reminders that are tracked within AwardWallet.'),
            (new Message('notification.channel.dep', 'mobile'))->setDesc('Flight Departure'),
            (new Message('notification.channel.dep.desc', 'mobile'))->setDesc('This channel is used to deliver flight departure reminders for the flights that are tracked within AwardWallet.'),
            (new Message('notification.channel.boarding', 'mobile'))->setDesc('Flight Boarding'),
            (new Message('notification.channel.boarding.desc', 'mobile'))->setDesc('This channel is used to deliver flight boarding reminders that are tracked within AwardWallet.'),
            (new Message('notification.channel.new_reservation', 'mobile'))->setDesc('New Travel Reservations'),
            (new Message('notification.channel.new_reservation.desc', 'mobile'))->setDesc('This channel is used to deliver notifications about the new travel reservations that get automatically added to your AwardWallet profile.'),
            (new Message('notification.channel.change_alert', 'mobile'))->setDesc('Flight Change'),
            (new Message('notification.channel.change_alert.desc', 'mobile'))->setDesc('This channel is used to deliver flight change alerts (such as seat changes, gate changes, flight delays and cancellations) for your flights that are tracked within AwardWallet.'),
            (new Message('notification.channel.flight_connection', 'mobile'))->setDesc('Flight Connection'),
            (new Message('notification.channel.flight_connection.desc', 'mobile'))->setDesc('This channel is used to deliver your flight connection info for the flights that get automatically added to your AwardWallet profile.'),
            (new Message('notification.channel.retail_cards', 'mobile'))->setDesc('Retail Loyalty Cards'),
            (new Message('notification.channel.retail_cards.desc', 'mobile'))->setDesc('This channel is designed to deliver your favorite retail loylaty cards with bar codes to your homescreen so that you could quickly scan the bar code at the point of sale.'),
            (new Message('notification.channel.promo', 'mobile'))->setDesc('Promotions and product updates'),
            (new Message('notification.channel.promo.desc', 'mobile'))->setDesc('This channel is designed to deliver promotions, such as sending an awesome deal to earn miles that you really should be aware of or product updates. We won\'t abuse this channel.'),
            (new Message('notification.channel.blog', 'mobile'))->setDesc('AwardWallet Blog Notifications'),
            (new Message('notification.channel.blog.desc', 'mobile'))->setDesc('This channel is designed to deliver blog post notifications when new posts are published in case if you oped in to receive such notifications.'),
            (new Message('notification.channel.services', 'mobile'))->setDesc('Retail Store Finder'),
            (new Message('notification.channel.services.desc', 'mobile'))->setDesc('This channel is used by AwardWallet to detect the moment when you come to a retail store in order to deliver your bonus card (including the barcode) to your home screen for an easy checkout.'),
            (new Message('notification.channel.awplus', 'mobile'))->setDesc('AwardWallet Plus Expiration'),
            (new Message('notification.channel.awplus.desc', 'mobile'))->setDesc('This channel is designed to deliver notifications about AwardWallet Plus expirations.'),

            (new Message('membership_email_settings_restriction', 'validators'))->setDesc('The "%optionName%" setting is available exclusively to AwardWallet Plus members. As an AwardWallet Free member, you can only unsubscribe from all of our emails.'),
        ];
    }

    protected function preSetData(FormEvent $event, $options)
    {
        $form = $event->getForm();
        $isBusiness = $options['isBusiness'];

        $freeVersionOptions = [
            'disabled' => $options['freeVersion'],
            'attr' => ['data-always-disabled' => $options['freeVersion'] ? 'true' : 'false'],
        ];

        // emails
        if ($isBusiness) {
            $form->add('emailBookingMessages', CheckboxType::class);
        } else {
            $form->add('emailDisableAll', CheckboxType::class);
            $form->add('emailExpire', ChoiceType::class, [
                'choices' => NotificationModel::getEmailExpireChoices(),
            ]);
            $form->add('emailRewardsActivity', ChoiceType::class, [
                'choices' => NotificationModel::getEmailRewardsChoices(),
            ]);
            $form->add('emailNewPlans', CheckboxType::class);
            $form->add('emailPlanChanges', CheckboxType::class);
            $form->add('emailCheckins', CheckboxType::class);
            $form->add('emailProductUpdates', CheckboxType::class);
            $form->add('emailOffers', CheckboxType::class, $freeVersionOptions);
            $form->add('emailNewBlogPosts', ChoiceType::class, [
                'choices' => NotificationModel::getEmailBlogPostsChoices(),
            ]);
            $form->add('emailInviteeReg', CheckboxType::class);
            //          TODO: uncomment after implement
            //          $form->add('emailConnected', CheckboxType::class);
            $form->add('emailNotConnected', CheckboxType::class);
        }

        // wp
        if ($isBusiness) {
            $form->add('wpBookingMessages', CheckboxType::class);
        } else {
            $form->add('wpDisableAll', CheckboxType::class);
            $form->add('wpExpire', CheckboxType::class);
            $form->add('wpRewardsActivity', CheckboxType::class);
            $form->add('wpNewPlans', CheckboxType::class);
            $form->add('wpPlanChanges', CheckboxType::class);
            $form->add('wpCheckins', CheckboxType::class);
            $form->add('wpProductUpdates', CheckboxType::class);
            $form->add('wpOffers', CheckboxType::class);
            $form->add('wpNewBlogPosts', CheckboxType::class);
            //        $form->add('wpInviteeReg', CheckboxType::class);
            //        TODO: uncomment after implement
            //        $form->add('wpConnected', CheckboxType::class);
            $form->add('wpNotConnected', CheckboxType::class);
        }

        // mp
        if ($isBusiness) {
            $form->add('mpBookingMessages', CheckboxType::class);
        } else {
            $form->add('mpDisableAll', CheckboxType::class);
            $form->add('mpExpire', CheckboxType::class);
            $form->add('mpRewardsActivity', CheckboxType::class);
            $form->add('mpRetailCards', CheckboxType::class);
            $form->add('mpNewPlans', CheckboxType::class);
            $form->add('mpPlanChanges', CheckboxType::class);
            $form->add('mpCheckins', CheckboxType::class);
            $form->add('mpProductUpdates', CheckboxType::class);
            $form->add('mpOffers', CheckboxType::class);
            $form->add('mpNewBlogPosts', CheckboxType::class);
            //          $form->add('mpInviteeReg', CheckboxType::class);
            //          TODO: uncomment after implement
            //          $form->add('mpConnected', CheckboxType::class);
            $form->add('mpNotConnected', CheckboxType::class);
        }
    }
}
