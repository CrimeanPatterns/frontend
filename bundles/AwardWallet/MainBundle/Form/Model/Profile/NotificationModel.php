<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

class NotificationModel extends AbstractEntityAwareModel
{
    public const BLOGPOST_NEW_NOTIFICATION_NEVER = 0;
    public const BLOGPOST_NEW_NOTIFICATION_IMMEDIATE = 1;
    public const BLOGPOST_NEW_NOTIFICATION_DAY = 2;
    public const BLOGPOST_NEW_NOTIFICATION_WEEK = 3;

    // Email

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailDisableAll = false;

    /**
     * @var int
     * @Assert\NotBlank
     * @Assert\Choice(callback = "getEmailExpireChoices")
     */
    private $emailExpire = Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7;

    /**
     * @var int
     * @Assert\NotBlank
     * @Assert\Choice(callback = "getEmailRewardsChoices")
     */
    private $emailRewardsActivity = REWARDS_NOTIFICATION_DAY;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailNewPlans = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailPlanChanges = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailCheckins = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailBookingMessages = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailProductUpdates = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailOffers = true;

    /**
     * @var int
     * @Assert\Choice(callback="getEmailBlogPostsChoices")
     */
    private $emailNewBlogPosts = self::BLOGPOST_NEW_NOTIFICATION_NEVER;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailInviteeReg = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailConnected = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $emailNotConnected = true;

    // WP

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpDisableAll = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpExpire = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpRewardsActivity = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpNewPlans = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpPlanChanges = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpCheckins = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpBookingMessages = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpProductUpdates = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpOffers = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpNewBlogPosts = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpInviteeReg = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpConnected = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $wpNotConnected = true;

    // MP

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpDisableAll = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpExpire = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpRewardsActivity = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpNewPlans = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpPlanChanges = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpCheckins = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpBookingMessages = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpProductUpdates = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpOffers = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpNewBlogPosts = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpInviteeReg = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpConnected = false;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpNotConnected = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $mpRetailCards = true;

    /**
     * @return bool
     */
    public function isEmailDisableAll()
    {
        return $this->emailDisableAll;
    }

    /**
     * @param bool $emailDisableAll
     * @return NotificationModel
     */
    public function setEmailDisableAll($emailDisableAll)
    {
        $this->emailDisableAll = $emailDisableAll;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmailExpire()
    {
        return $this->emailExpire;
    }

    /**
     * @param int $emailExpire
     * @return NotificationModel
     */
    public function setEmailExpire($emailExpire)
    {
        $this->emailExpire = $emailExpire;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmailRewardsActivity()
    {
        return $this->emailRewardsActivity;
    }

    /**
     * @param int $emailRewardsActivity
     * @return NotificationModel
     */
    public function setEmailRewardsActivity($emailRewardsActivity)
    {
        $this->emailRewardsActivity = $emailRewardsActivity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailNewPlans()
    {
        return $this->emailNewPlans;
    }

    /**
     * @param bool $emailNewPlans
     * @return NotificationModel
     */
    public function setEmailNewPlans($emailNewPlans)
    {
        $this->emailNewPlans = $emailNewPlans;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailPlanChanges()
    {
        return $this->emailPlanChanges;
    }

    /**
     * @param bool $emailPlanChanges
     * @return NotificationModel
     */
    public function setEmailPlanChanges($emailPlanChanges)
    {
        $this->emailPlanChanges = $emailPlanChanges;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailCheckins()
    {
        return $this->emailCheckins;
    }

    /**
     * @param bool $emailCheckins
     * @return NotificationModel
     */
    public function setEmailCheckins($emailCheckins)
    {
        $this->emailCheckins = $emailCheckins;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailBookingMessages()
    {
        return $this->emailBookingMessages;
    }

    /**
     * @param bool $emailBookingMessages
     * @return NotificationModel
     */
    public function setEmailBookingMessages($emailBookingMessages)
    {
        $this->emailBookingMessages = $emailBookingMessages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailProductUpdates()
    {
        return $this->emailProductUpdates;
    }

    /**
     * @param bool $emailProductUpdates
     * @return NotificationModel
     */
    public function setEmailProductUpdates($emailProductUpdates)
    {
        $this->emailProductUpdates = $emailProductUpdates;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailOffers()
    {
        return $this->emailOffers;
    }

    /**
     * @param bool $emailOffers
     * @return NotificationModel
     */
    public function setEmailOffers($emailOffers)
    {
        $this->emailOffers = $emailOffers;

        return $this;
    }

    public function getEmailNewBlogPosts(): int
    {
        return $this->emailNewBlogPosts;
    }

    public function setEmailNewBlogPosts(?int $emailNewBlogPosts): self
    {
        $this->emailNewBlogPosts = $emailNewBlogPosts;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailInviteeReg()
    {
        return $this->emailInviteeReg;
    }

    /**
     * @param bool $emailInviteeReg
     * @return NotificationModel
     */
    public function setEmailInviteeReg($emailInviteeReg)
    {
        $this->emailInviteeReg = $emailInviteeReg;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailConnected()
    {
        return $this->emailConnected;
    }

    /**
     * @param bool $emailConnected
     * @return NotificationModel
     */
    public function setEmailConnected($emailConnected)
    {
        $this->emailConnected = $emailConnected;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailNotConnected()
    {
        return $this->emailNotConnected;
    }

    /**
     * @param bool $emailNotConnected
     * @return NotificationModel
     */
    public function setEmailNotConnected($emailNotConnected)
    {
        $this->emailNotConnected = $emailNotConnected;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpDisableAll()
    {
        return $this->wpDisableAll;
    }

    /**
     * @param bool $wpDisableAll
     * @return NotificationModel
     */
    public function setWpDisableAll($wpDisableAll)
    {
        $this->wpDisableAll = $wpDisableAll;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpExpire()
    {
        return $this->wpExpire;
    }

    /**
     * @param bool $wpExpire
     * @return NotificationModel
     */
    public function setWpExpire($wpExpire)
    {
        $this->wpExpire = $wpExpire;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpRewardsActivity()
    {
        return $this->wpRewardsActivity;
    }

    /**
     * @param bool $wpRewardsActivity
     * @return NotificationModel
     */
    public function setWpRewardsActivity($wpRewardsActivity)
    {
        $this->wpRewardsActivity = $wpRewardsActivity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpNewPlans()
    {
        return $this->wpNewPlans;
    }

    /**
     * @param bool $wpNewPlans
     * @return NotificationModel
     */
    public function setWpNewPlans($wpNewPlans)
    {
        $this->wpNewPlans = $wpNewPlans;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpPlanChanges()
    {
        return $this->wpPlanChanges;
    }

    /**
     * @param bool $wpPlanChanges
     * @return NotificationModel
     */
    public function setWpPlanChanges($wpPlanChanges)
    {
        $this->wpPlanChanges = $wpPlanChanges;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpCheckins()
    {
        return $this->wpCheckins;
    }

    /**
     * @param bool $wpCheckins
     * @return NotificationModel
     */
    public function setWpCheckins($wpCheckins)
    {
        $this->wpCheckins = $wpCheckins;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpBookingMessages()
    {
        return $this->wpBookingMessages;
    }

    /**
     * @param bool $wpBookingMessages
     * @return NotificationModel
     */
    public function setWpBookingMessages($wpBookingMessages)
    {
        $this->wpBookingMessages = $wpBookingMessages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpProductUpdates()
    {
        return $this->wpProductUpdates;
    }

    /**
     * @param bool $wpProductUpdates
     * @return NotificationModel
     */
    public function setWpProductUpdates($wpProductUpdates)
    {
        $this->wpProductUpdates = $wpProductUpdates;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpOffers()
    {
        return $this->wpOffers;
    }

    /**
     * @param bool $wpOffers
     * @return NotificationModel
     */
    public function setWpOffers($wpOffers)
    {
        $this->wpOffers = $wpOffers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpNewBlogPosts()
    {
        return $this->wpNewBlogPosts;
    }

    /**
     * @param bool $wpNewBlogPosts
     * @return NotificationModel
     */
    public function setWpNewBlogPosts($wpNewBlogPosts)
    {
        $this->wpNewBlogPosts = $wpNewBlogPosts;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpInviteeReg()
    {
        return $this->wpInviteeReg;
    }

    /**
     * @param bool $wpInviteeReg
     * @return NotificationModel
     */
    public function setWpInviteeReg($wpInviteeReg)
    {
        $this->wpInviteeReg = $wpInviteeReg;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpConnected()
    {
        return $this->wpConnected;
    }

    /**
     * @param bool $wpConnected
     * @return NotificationModel
     */
    public function setWpConnected($wpConnected)
    {
        $this->wpConnected = $wpConnected;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpNotConnected()
    {
        return $this->wpNotConnected;
    }

    /**
     * @param bool $wpNotConnected
     * @return NotificationModel
     */
    public function setWpNotConnected($wpNotConnected)
    {
        $this->wpNotConnected = $wpNotConnected;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpDisableAll()
    {
        return $this->mpDisableAll;
    }

    /**
     * @param bool $mpDisableAll
     * @return NotificationModel
     */
    public function setMpDisableAll($mpDisableAll)
    {
        $this->mpDisableAll = $mpDisableAll;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpExpire()
    {
        return $this->mpExpire;
    }

    /**
     * @param bool $mpExpire
     * @return NotificationModel
     */
    public function setMpExpire($mpExpire)
    {
        $this->mpExpire = $mpExpire;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpRewardsActivity()
    {
        return $this->mpRewardsActivity;
    }

    /**
     * @param bool $mpRewardsActivity
     * @return NotificationModel
     */
    public function setMpRewardsActivity($mpRewardsActivity)
    {
        $this->mpRewardsActivity = $mpRewardsActivity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpNewPlans()
    {
        return $this->mpNewPlans;
    }

    /**
     * @param bool $mpNewPlans
     * @return NotificationModel
     */
    public function setMpNewPlans($mpNewPlans)
    {
        $this->mpNewPlans = $mpNewPlans;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpPlanChanges()
    {
        return $this->mpPlanChanges;
    }

    /**
     * @param bool $mpPlanChanges
     * @return NotificationModel
     */
    public function setMpPlanChanges($mpPlanChanges)
    {
        $this->mpPlanChanges = $mpPlanChanges;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpCheckins()
    {
        return $this->mpCheckins;
    }

    /**
     * @param bool $mpCheckins
     * @return NotificationModel
     */
    public function setMpCheckins($mpCheckins)
    {
        $this->mpCheckins = $mpCheckins;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpBookingMessages()
    {
        return $this->mpBookingMessages;
    }

    /**
     * @param bool $mpBookingMessages
     * @return NotificationModel
     */
    public function setMpBookingMessages($mpBookingMessages)
    {
        $this->mpBookingMessages = $mpBookingMessages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpProductUpdates()
    {
        return $this->mpProductUpdates;
    }

    /**
     * @param bool $mpProductUpdates
     * @return NotificationModel
     */
    public function setMpProductUpdates($mpProductUpdates)
    {
        $this->mpProductUpdates = $mpProductUpdates;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpOffers()
    {
        return $this->mpOffers;
    }

    /**
     * @param bool $mpOffers
     * @return NotificationModel
     */
    public function setMpOffers($mpOffers)
    {
        $this->mpOffers = $mpOffers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpNewBlogPosts()
    {
        return $this->mpNewBlogPosts;
    }

    /**
     * @param bool $mpNewBlogPosts
     * @return NotificationModel
     */
    public function setMpNewBlogPosts($mpNewBlogPosts)
    {
        $this->mpNewBlogPosts = $mpNewBlogPosts;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpInviteeReg()
    {
        return $this->mpInviteeReg;
    }

    /**
     * @param bool $mpInviteeReg
     * @return NotificationModel
     */
    public function setMpInviteeReg($mpInviteeReg)
    {
        $this->mpInviteeReg = $mpInviteeReg;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpConnected()
    {
        return $this->mpConnected;
    }

    /**
     * @param bool $mpConnected
     * @return NotificationModel
     */
    public function setMpConnected($mpConnected)
    {
        $this->mpConnected = $mpConnected;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpNotConnected()
    {
        return $this->mpNotConnected;
    }

    /**
     * @param bool $mpNotConnected
     * @return NotificationModel
     */
    public function setMpNotConnected($mpNotConnected)
    {
        $this->mpNotConnected = $mpNotConnected;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpRetailCards()
    {
        return $this->mpRetailCards;
    }

    /**
     * @param bool $mpRetailCards
     * @return NotificationModel
     */
    public function setMpRetailCards($mpRetailCards)
    {
        $this->mpRetailCards = $mpRetailCards;

        return $this;
    }

    public function getMobileDumpedSettings()
    {
        return [
            'mpDisableAll' => $this->isMpDisableAll(),
            'mpBookingMessages' => $this->isMpBookingMessages(),
            'mpCheckins' => $this->isMpCheckins(),
            'mpRetailCards' => $this->isMpRetailCards(),
        ];
    }

    public static function getEmailRewardsChoices()
    {
        return [
            'notification.rewards-activity.day' => REWARDS_NOTIFICATION_DAY,
            'notification.rewards-activity.week' => REWARDS_NOTIFICATION_WEEK,
            'notification.rewards-activity.month' => REWARDS_NOTIFICATION_MONTH,
            'notification.rewards-activity.never' => REWARDS_NOTIFICATION_NEVER,
        ];
    }

    public static function getEmailExpireChoices()
    {
        return [
            'notification.rewards-expiration.every_day' => Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7,
            'notification.rewards-expiration.7_days' => Usr::EMAIL_EXPIRATION_90_60_30_7,
            'notification.rewards-activity.never' => Usr::EMAIL_EXPIRATION_NEVER,
        ];
    }

    public static function getEmailBlogPostsChoices(): array
    {
        return [
            'immediate' => self::BLOGPOST_NEW_NOTIFICATION_IMMEDIATE,
            'notification.rewards-activity.day' => self::BLOGPOST_NEW_NOTIFICATION_DAY,
            'notification.rewards-activity.week' => self::BLOGPOST_NEW_NOTIFICATION_WEEK,
            'notification.rewards-activity.never' => self::BLOGPOST_NEW_NOTIFICATION_NEVER,
        ];
    }
}
