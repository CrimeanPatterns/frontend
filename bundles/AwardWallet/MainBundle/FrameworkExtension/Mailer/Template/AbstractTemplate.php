<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

abstract class AbstractTemplate
{
    public const STATUS_READY = 1;
    public const STATUS_NOT_READY = 2;

    /**
     * @var Usr|Useragent|null recipient of the email
     */
    protected $user;

    /**
     * @var string the recipient of the email. Personal email (not business)
     */
    protected $email;

    /**
     * @var bool email for business (to business admin)
     */
    protected $businessArea = false;

    /**
     * @var bool
     */
    protected $enableUnsubscribe = true;

    /**
     * @var bool business or personal unsubscribe link
     */
    protected $businessUnsubscribe = false;

    /**
     * @var string force lang
     */
    protected $lang;

    /**
     * @var string force locale
     */
    protected $locale;

    /**
     * @var bool debug environment
     */
    protected $debug = false;

    public function __construct($to = null, bool $toBusiness = false)
    {
        if (!empty($to)) {
            if ($to instanceof Usr) {
                $this->toUser($to, $toBusiness);
            } elseif ($to instanceof Useragent) {
                $this->toFamilyMember($to);
            } elseif (is_string($to)) {
                $this->toEmail($to, $toBusiness);
            }
        }
    }

    /**
     * @param Usr $user - recipient of the email. Personal email (not business)
     * @param bool $toBusiness - email for business (to business admin)
     * @param string|null $customEmail - another mail address
     * @return self
     */
    public function toUser(Usr $user, bool $toBusiness = false, ?string $customEmail = null)
    {
        $to = $customEmail ?? $user->getEmail();

        if (empty($to)) {
            throw new \InvalidArgumentException('Email is required');
        }

        $this->toEmail($to, $toBusiness);
        $this->user = $user;

        return $this;
    }

    /**
     * @param Useragent $ua - recipient of the email. Block with password recovery will not be shown.
     *        AgentID must be personal account (not business)
     * @param string|null $customEmail - another mail address
     * @return self
     */
    public function toFamilyMember(Useragent $ua, ?string $customEmail = null)
    {
        $to = $customEmail ?? $ua->getEmail();

        if (empty($to)) {
            throw new \InvalidArgumentException('Email is required');
        }

        $this->toEmail($to, false);
        $this->user = $ua;

        return $this;
    }

    /**
     * @param string $email - recipient of the email. Personal email (not business)
     * @param bool|false $toBusiness - email for business (to business admin)
     * @return self
     */
    public function toEmail(string $email, bool $toBusiness = false)
    {
        $this->user = null;
        $this->email = $email;
        $this->businessArea = $toBusiness;

        return $this;
    }

    /**
     * @return self
     */
    public function setRegionalSettings(?string $locale, ?string $lang)
    {
        $this->locale = $locale;
        $this->lang = $lang;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @return self
     */
    public function enableUnsubscribe(bool $enableUnsubscribe, bool $businessUnsubscribe = false)
    {
        $this->enableUnsubscribe = $enableUnsubscribe;
        $this->businessUnsubscribe = $businessUnsubscribe;

        return $this;
    }

    public function isEnableUnsubscribe(): bool
    {
        return $this->enableUnsubscribe;
    }

    public function isBusinessUnsubscribe(): bool
    {
        return $this->businessUnsubscribe;
    }

    /**
     * @return self
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return Useragent|Usr|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return self
     */
    public function setEmail(string $customEmail)
    {
        $this->email = $customEmail;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function hasEmail(): bool
    {
        return !empty($this->email);
    }

    /**
     * @return self
     */
    public function setBusinessArea(bool $businessArea)
    {
        $this->businessArea = $businessArea;

        return $this;
    }

    public function isBusinessArea(): bool
    {
        return $this->businessArea;
    }

    public function getTemplateVars(): array
    {
        return get_object_vars($this);
    }

    public static function getEmailKind(): string
    {
        $class = static::class;
        $parts = explode('\\', $class);
        $class = array_pop($parts);

        return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "_$1", $class));
    }

    public static function getDescription(): string
    {
        throw new \RuntimeException('getDescription() must be implemented by subclasses');
    }

    public static function getKeywords(): array
    {
        return [];
    }

    /**
     * @return int ready or not ready mail
     */
    public static function getStatus(): int
    {
        return static::STATUS_NOT_READY;
    }

    /**
     * @param array $options
     * @return AbstractTemplate
     */
    public static function createFake(ContainerInterface $container, $options = [])
    {
        throw new \RuntimeException('createFake() must be implemented by subclasses');
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }
}
