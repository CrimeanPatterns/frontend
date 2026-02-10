<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Form\Transformer\Profile\NotificationTransformer;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationSettings implements TranslationContainerInterface
{
    public const KIND_EMAIL = 1;
    public const KIND_WP = 2;
    public const KIND_MP = 3;
    public const KIND_BUSINESS = 4;

    public const APP_HEADER = 'app_header';

    public const OPTION_LIST = 1;
    public const OPTION_TABLE = 2;

    private NotificationTransformer $transformer;

    private TranslatorInterface $translator;

    private UsrRepository $usrRepository;

    public function __construct(NotificationTransformer $transformer, TranslatorInterface $translator, UsrRepository $usrRepository)
    {
        $this->transformer = $transformer;
        $this->translator = $translator;
        $this->usrRepository = $usrRepository;
    }

    /**
     * @return NotificationModel
     */
    public function getSettingsModel(Usr $user)
    {
        return $this->transformer->transform($user);
    }

    /**
     * @param array $order self::KIND_* constants
     * @return array
     */
    public function getSettingsView(Usr $user, array $order, $mode = self::OPTION_LIST)
    {
        $model = $this->getSettingsModel($user);
        $typeRewards = $this->translator->trans('rewards');
        $typeTravel = $this->translator->trans('travel');
        $typeOther = $this->translator->trans('other');

        $businessAdmin = !is_null($this->usrRepository->getBusinessByUser($user));

        // emails
        $isDisableAll = $model->isEmailDisableAll();
        $ra = $isDisableAll ? REWARDS_NOTIFICATION_NEVER : $model->getEmailRewardsActivity();
        $re = $isDisableAll ? Usr::EMAIL_EXPIRATION_NEVER : $model->getEmailExpire();
        $blogPostNew = $isDisableAll ? NotificationModel::BLOGPOST_NEW_NOTIFICATION_NEVER : $model->getEmailNewBlogPosts();
        $emailNotifications = [
            $typeRewards => [
                $this->getView(
                    'notification.expiration',
                    $this->translator->trans(array_flip(NotificationModel::getEmailExpireChoices())[$re]),
                    'messages',
                    ['formFieldName' => 'emailExpire']
                ),
                $this->getView(
                    'notification.rewards-activity',
                    $this->translator->trans(array_flip(NotificationModel::getEmailRewardsChoices())[$ra]),
                    'messages',
                    ['formFieldName' => 'emailRewardsActivity']
                ),
            ],
            $typeTravel => array_filter([
                $this->getView('notification.new-travel-plan', !$isDisableAll && $model->isEmailNewPlans(), 'messages', ['formFieldName' => 'emailNewPlans']),
                $this->getView('notification.change-travel-plan', !$isDisableAll && $model->isEmailPlanChanges(), 'messages', ['formFieldName' => 'emailPlanChanges']),
                $this->getView('notification.checkinreminder', !$isDisableAll && $model->isEmailCheckins(), 'messages', ['formFieldName' => 'emailCheckins']),
            ]),
            $typeOther => [
                $this->getView('notification.product-updates', !$isDisableAll && $model->isEmailProductUpdates(), 'messages', ['formFieldName' => 'emailProductUpdates']),
                $this->getView('notification.offers', !$isDisableAll && $model->isEmailOffers(), 'messages', ['formFieldName' => 'emailOffers']),
                $this->getView('notification.blog-new-post',
                    $this->translator->trans(array_flip(NotificationModel::getEmailBlogPostsChoices())[$blogPostNew]),
                    'messages',
                    ['formFieldName' => 'emailNewBlogPosts']
                ),
                $this->getView('notification.register', !$isDisableAll && $model->isEmailInviteeReg(), 'messages', ['formFieldName' => 'emailInviteeReg']),
                //              TODO: uncomment after implement
                //              $this->getView('notification.connected-alerts', !$isDisableAll && $model->isEmailConnected()),
                $this->getView('notification.not-connected-alerts', !$isDisableAll && $model->isEmailNotConnected(), 'messages', ['formFieldName' => 'emailNotConnected']),
            ],
        ];

        if ($mode == self::OPTION_TABLE) {
            $emailNotifications[$typeRewards][] = $this->getView(
                $this->translator->trans('notification.channel.retail_cards', [], 'mobile'),
                ""
            );
        }

        // desktop web push
        $isDisableAll = $model->isWpDisableAll();
        $wpNotifications = [
            $typeRewards => [
                $this->getView('notification.expiration', !$isDisableAll && $model->isWpExpire()),
                $this->getView('notification.rewards-activity', !$isDisableAll && $model->isWpRewardsActivity()),
            ],
            $typeTravel => array_filter([
                $this->getView('notification.new-travel-plan', !$isDisableAll && $model->isWpNewPlans()),
                $this->getView('notification.change-travel-plan', !$isDisableAll && $model->isWpPlanChanges()),
                $this->getView('notification.checkinreminder', !$isDisableAll && $model->isWpCheckins()),
            ]),
            $typeOther => [
                $this->getView('notification.product-updates', !$isDisableAll && $model->isWpProductUpdates()),
                $this->getView('notification.offers', !$isDisableAll && $model->isWpOffers()),
                $this->getView('notification.blog-new-post', !$isDisableAll && $model->isWpNewBlogPosts()),

                //              TODO: uncomment after implement
                //              $this->getView('notification.connected-alerts', !$isDisableAll && $model->isWpConnected()),
                $this->getView('notification.not-connected-alerts', !$isDisableAll && $model->isWpNotConnected()),
            ],
        ];

        if ($mode == self::OPTION_TABLE) {
            $wpNotifications[$typeRewards][] = $this->getView(
                $this->translator->trans('notification.channel.retail_cards', [], 'mobile'),
                ""
            );
            array_splice($wpNotifications[$typeOther], 3, 0, [$this->getView(
                $this->translator->trans('notification.register'),
                ""
            )]);
        }

        // mobile app settings
        $isDisableAll = $model->isMpDisableAll();
        $mpNotifications = [
            self::APP_HEADER => [
                $this->getView('app-notifications-sounds', false, 'mobile', ['setting' => 'sound']),
                $this->getView('app-notifications-vibrate', false, 'mobile', ['setting' => 'vibrate']),
            ],
            $typeRewards => [
                $this->getView('notification.expiration', !$isDisableAll && $model->isMpExpire(), 'messages', ['formFieldName' => 'mpExpire']),
                $this->getView('notification.rewards-activity', !$isDisableAll && $model->isMpRewardsActivity(), 'messages', ['formFieldName' => 'mpRewardsActivity']),
                $this->getView(
                    'notification.channel.retail_cards',
                    !$isDisableAll && $model->isMpRetailCards(),
                    'mobile',
                    ['formFieldName' => 'mpRetailCards']
                ),
            ],
            $typeTravel => array_filter([
                $this->getView('notification.new-travel-plan', !$isDisableAll && $model->isMpNewPlans(), 'messages', ['formFieldName' => 'mpNewPlans']),
                $this->getView('notification.change-travel-plan', !$isDisableAll && $model->isMpPlanChanges(), 'messages', ['formFieldName' => 'mpPlanChanges']),
                $this->getView('notification.checkinreminder', !$isDisableAll && $model->isMpCheckins(), 'messages', ['formFieldName' => 'mpCheckins']),
            ]),
            $typeOther => [
                $this->getView('notification.product-updates', !$isDisableAll && $model->isMpProductUpdates(), 'messages', ['formFieldName' => 'mpProductUpdates']),
                $this->getView('notification.offers', !$isDisableAll && $model->isMpOffers(), 'messages', ['formFieldName' => 'mpOffers']),
                $this->getView('notification.blog-new-post', !$isDisableAll && $model->isMpNewBlogPosts(), 'messages', ['formFieldName' => 'mpNewBlogPosts']),
                //              TODO: uncomment after implement
                //              $this->getView('notification.connected-alerts', !$isDisableAll && $model->isMpConnected()),
                $this->getView('notification.not-connected-alerts', !$isDisableAll && $model->isMpNotConnected(), 'messages', ['formFieldName' => 'mpNotConnected']),
            ],
        ];

        if ($mode == self::OPTION_TABLE) {
            array_splice($mpNotifications[$typeOther], 3, 0, [$this->getView(
                $this->translator->trans('notification.register'),
                ""
            )]);
        }

        $result = [];

        foreach ($order as $kind) {
            switch ($kind) {
                case self::KIND_EMAIL:
                    $result[] = [
                        'group' => $this->translator->trans('email-notifications'),
                        'items' => $emailNotifications,
                        'kind' => $kind,
                    ];

                    break;

                case self::KIND_WP:
                    $result[] = [
                        'group' => $this->translator->trans('desktop-notifications'),
                        'items' => $wpNotifications,
                        'kind' => $kind,
                    ];

                    break;

                case self::KIND_MP:
                    $result[] = [
                        'group' => $this->translator->trans('push-notifications'),
                        'items' => $mpNotifications,
                        'kind' => $kind,
                    ];

                    break;
            }
        }

        if ($mode == self::OPTION_TABLE && sizeof($result)) {
            $table = [];

            foreach ($result as $group) {
                foreach ($group['items'] as $type => $items) {
                    if (!isset($table[$type])) {
                        $table[$type] = [];
                    }

                    foreach ($items as $item) {
                        if (!isset($table[$type][$item['title']])) {
                            $table[$type][$item['title']] = [];
                        }
                        $table[$type][$item['title']][] = $item;
                    }
                }
            }

            return $table;
        }

        return $result;
    }

    public function getBusinessSettingsView(Usr $user): array
    {
        $model = $this->getSettingsModel($user);

        $businessNotifications = [
            'booking-activities' => [
                $this->getView('notification.booking', $model->isMpBookingMessages()),
                $this->getView('notification.booking', $model->isWpBookingMessages()),
                $this->getView('notification.booking', $model->isEmailBookingMessages()),
            ],
        ];

        $result = [];

        $result[] = [
            'group' => $this->translator->trans('business-notifications'),
            'items' => $businessNotifications,
        ];

        $table = [];

        foreach ($result as $group) {
            foreach ($group['items'] as $type => $items) {
                if (!isset($table[$type])) {
                    $table[$type] = [];
                }

                foreach ($items as $item) {
                    if (!isset($table[$type][$item['title']])) {
                        $table[$type][$item['title']] = [];
                    }
                    $table[$type][$item['title']][] = $item;
                }
            }
        }

        return $table;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('notifications'))->setDesc('Notifications'),
            (new Message('mobile'))->setDesc('Mobile'),
            (new Message('desktop'))->setDesc('Desktop'),
            (new Message('email'))->setDesc('Email'),
            (new Message('notification.disable-all'))->setDesc('Disable All Notifications'),
            (new Message('notification.disable-all.help'))->setDesc('We only send you account security alerts or emails like “I Forgot My Password”. All other notifications are disabled.'),
            (new Message('rewards'))->setDesc('Rewards'),
            (new Message('notification.expiration'))->setDesc('Rewards Expiration'),
            (new Message('notification.expiration.help-v2'))->setDesc('If your rewards are going to expire, we send you reminders based on your selected notification preference.'),
            (new Message('notification.rewards-activity'))->setDesc('Rewards Activity'),
            (new Message('notification.rewards-activity.help'))->setDesc('For mobile and desktop notifications we alert you in real-time as we detect a change in your reward balances. For the email notifications, we aggregate your changed balances and send notices once per day, week or month based on your preference.'),
            (new Message('notification.rewards-activity.day'))->setDesc('Daily'),
            (new Message('notification.rewards-activity.week'))->setDesc('Weekly'),
            (new Message('notification.rewards-activity.month'))->setDesc('Monthly'),
            (new Message('notification.rewards-activity.never'))->setDesc('Never'),
            (new Message('notification.rewards-expiration.every_day'))->setDesc('90, 60, 30, and every day for the last 7 days before expiration'),
            (new Message('notification.rewards-expiration.7_days'))->setDesc('90, 60, 30, and 7 days before expiration'),
            (new Message('travel'))->setDesc('Travel'),
            (new Message('notification.new-travel-plan'))->setDesc('New Travel Reservations'),
            (new Message('notification.new-travel-plan.help'))->setDesc('Sent whenever we automatically import items to your AwardWallet travel timeline.'),
            (new Message('notification.change-travel-plan'))->setDesc('Changes to Travel Reservations'),
            (new Message('notification.change-travel-plan.help'))->setDesc('Only sent if we detect a significant change to your reservation. Changes we monitor include seat changes, flight delays, gate changes, and upgrades.'),
            (new Message('notification.checkinreminder'))->setDesc('Flight Reminders'),
            (new Message('notification.checkinreminder.help'))->setDesc('We send check-in reminders through mobile, desktop, and email. Flight departure (4 hours before scheduled departure) and flight boarding alerts are sent only via mobile and desktop.'),
            (new Message('notification.booking'))->setDesc('Booking Service Messages'),
            (new Message('notification.booking-business'))->setDesc('Booking activities'),
            (new Message('notification.booking-messages.help'))->setDesc('Alerts from users of the booking service whose booking requests are assigned to you.'),
            (new Message('other'))->setDesc('Other'),
            (new Message('notification.product-updates'))->setDesc('Product Updates'),
            (new Message('notification.product-updates.help'))->setDesc('Only sent when we make significant product updates.'),
            (new Message('notification.offers'))->setDesc('Promotional Offers'),
            (new Message('notification.offers.help'))->setDesc('If we are aware of a new promotion such as a limited time bonus on a credit card we notify you. We may not always send it through all 3 channels even if you have all 3 checkboxes checked.'),
            (new Message('notification.blog-new-post'))->setDesc('New Blog Posts'),
            (new Message('notification.blog-new-post.help'))->setDesc('Real-time notifications of new articles posted at AwardWallet.com/blog.'),
            (new Message('notification.register'))->setDesc('User Referrals'),
            (new Message('notification.register.help'))->setDesc('If you use your personal referral link to invite users to AwardWallet, we notify you every time someone uses that link to register.'),
            (new Message('notification.connected-alerts'))->setDesc('Connected User Alerts'),
            (new Message('notification.connected-alerts.help'))->setDesc('If you have connected users in your AwardWallet profile, you can choose to receive notifications that are intended for those users. Recommended if you manage rewards/travel for family members.'),
            (new Message('notification.not-connected-alerts'))->setDesc('Not-Connected Member Alerts'),
            (new Message('notification.not-connected-alerts.help'))->setDesc('If you have family members that you added to your profile just as names (they don’t have their own AwardWallet accounts connected to you) you can choose if you wish to receive alerts for those people.'),

            (new Message('email-notifications'))->setDesc('Email Notifications'),
            (new Message('push-notifications'))->setDesc('Push Notifications'),
            (new Message('business-notifications'))->setDesc('Business Notifications'),
            (new Message('notification.disable-all-email'))->setDesc('Disable All Email Notifications'),
            (new Message('notification.disable-all-push'))->setDesc('Disable All Push Notifications'),
            (new Message('app-notifications-sounds', 'mobile'))->setDesc('Sounds'),
            (new Message('app-notifications-vibrate', 'mobile'))->setDesc('Vibrate'),
        ];
    }

    private function getView($title, $value, $domain = 'messages', ?array $attr = null)
    {
        return [
            'title' => $this->translator->trans($title, [], $domain),
            'status' => $value,
            'attr' => $attr,
        ];
    }
}
