<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\OwnableTrait;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\Template;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\EntityContainerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AccountModel.
 *
 * @AwAssert\ConstraintReference(
 *     sourceClass = Account::class,
 *     sourceProperty = {
 *         "comment",
 *         "goal"
 *     }
 * )
 * @AwAssert\ConditionOld(
 *     if = "isCustom",
 *     then = {
 *          @AwAssert\ConstraintReference(
 *              sourceClass = Account::class,
 *              sourceProperty = "kind"
 *          )
 *     }
 * )
 */
class AccountModel implements EntityContainerInterface
{
    use OwnableTrait {
        setOwner as protected traitSetOwner;
    }

    /**
     * @var bool
     */
    protected $disableClientPasswordAccess;

    /**
     * @var string|\DateTimeInterface
     */
    private $login;

    /**
     * @var string|\DateTimeInterface
     */
    private $login2;

    /**
     * @var string|\DateTimeInterface
     */
    private $login3;

    /**
     * @var string
     */
    private $balance;

    /**
     * @var string
     */
    private $kind;

    /**
     * @var string
     */
    private $pass;

    /**
     * @var string
     * @Assert\Choice(choices=Account::SAVE_PASSWORD_OPTIONS)
     */
    private $savePassword;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     */
    private $programName;

    /**
     * @var string
     */
    private $loginUrl;

    /**
     * @var string
     */
    private $goal;

    /**
     * @var \DateTime
     */
    private $expirationDate;

    /**
     * @var bool
     */
    private $disabled;

    /**
     * @var bool
     */
    private $disableBackgroundUpdating;

    /**
     * @var string
     */
    private $dontTrackExpiration;

    /**
     * @var bool
     */
    private $notAffiliated;

    /**
     * @var string
     */
    private $authInfo;

    /**
     * @var Useragent[]
     */
    private $useragents;

    /**
     * @var bool
     */
    private $isarchived;

    /**
     * @var Account
     */
    private $entity;
    /**
     * @var Account
     */
    private $oldEntity;

    /**
     * @var Template
     */
    private $template;

    /**
     * @var bool
     */
    private $disableExtension;

    /** @var \DateTime */
    private $balanceWatchStartDate;

    /** @var bool */
    private $balanceWatch = false;

    /** @var int */
    private $pointsSource;

    /** @var Provider */
    private $transferFromProvider;

    private ?string $sourceProgramRegion = '';
    private ?string $targetProgramRegion = '';

    /** @var float */
    private $expectedPoints;

    /** @var \DateTime */
    private $transferRequestDate;

    /** @var Currency|null */
    private $currency;

    /** @var string */
    private $customEliteLevel;

    /**
     * @return Account
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param Account $entity
     * @return AccountModel
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        $this->oldEntity = clone $entity;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getUserid()
    {
        return $this->user;
    }

    public function setUserid($userid)
    {
        if (null !== $this->user && $this->user !== $userid) {
            $this->useragents = new ArrayCollection();
        }
        $this->user = $userid;

        return $this;
    }

    /**
     * @return Useragent
     */
    public function getUseragentid()
    {
        return $this->userAgent;
    }

    /**
     * @param Useragent $useragentid
     */
    public function setUseragentid($useragentid)
    {
        $this->userAgent = $useragentid;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return AccountModel
     */
    public function setLogin($login)
    {
        if ($login instanceof \DateTimeInterface) {
            $login = $login->format('Y-m-d');
        }

        $this->login = $login;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin2()
    {
        return $this->login2;
    }

    /**
     * @param string|\DateTimeInterface $login2
     * @return AccountModel
     */
    public function setLogin2($login2)
    {
        if ($login2 instanceof \DateTimeInterface) {
            $login2 = $login2->format('Y-m-d');
        }

        $this->login2 = $login2;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin3()
    {
        return $this->login3;
    }

    /**
     * @return AccountModel
     */
    public function setLogin3($login3)
    {
        if ($login3 instanceof \DateTimeInterface) {
            $login3 = $login3->format('Y-m-d');
        }

        $this->login3 = $login3;

        return $this;
    }

    /**
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param string $balance
     * @return AccountModel
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     * @return AccountModel
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     * @return AccountModel
     */
    public function setPass($pass)
    {
        $this->pass = $pass;

        return $this;
    }

    public function getSavePassword()
    {
        return $this->savePassword;
    }

    public function setSavePassword($savePassword): self
    {
        $this->savePassword = $savePassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return AccountModel
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getProgramName()
    {
        return $this->programName;
    }

    /**
     * @param string $programName
     * @return AccountModel
     */
    public function setProgramName($programName)
    {
        $this->programName = $programName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * @param string $loginUrl
     * @return AccountModel
     */
    public function setLoginUrl($loginUrl)
    {
        $this->loginUrl = $loginUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getGoal()
    {
        return $this->goal;
    }

    /**
     * @param string $goal
     * @return AccountModel
     */
    public function setGoal($goal)
    {
        $this->goal = $goal;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param string $expirationDate
     * @return AccountModel
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     * @return AccountModel
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getDontTrackExpiration()
    {
        return $this->dontTrackExpiration;
    }

    /**
     * @param string $dontTrackExpiration
     * @return AccountModel
     */
    public function setDontTrackExpiration($dontTrackExpiration)
    {
        $this->dontTrackExpiration = $dontTrackExpiration;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNotRelated()
    {
        return $this->notAffiliated;
    }

    /**
     * @param bool $notAffiliated
     * @return AccountModel
     */
    public function setNotRelated($notAffiliated)
    {
        $this->notAffiliated = $notAffiliated;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthInfo()
    {
        return $this->authInfo;
    }

    /**
     * @param string $authInfo
     * @return AccountModel
     */
    public function setAuthInfo($authInfo)
    {
        $this->authInfo = $authInfo;

        return $this;
    }

    /**
     * @return Useragent[]
     */
    public function getUseragents()
    {
        return $this->useragents;
    }

    /**
     * @param Useragent[]
     * @return AccountModel
     */
    public function setUseragents($useragents)
    {
        $this->useragents = $useragents;

        return $this;
    }

    public function getIsArchived(): ?bool
    {
        return $this->isarchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isarchived = $isArchived;

        return $this;
    }

    public function isCustom()
    {
        return !$this->entity->getProviderid();
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param Template $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return Account
     */
    public function getOldEntity()
    {
        return $this->oldEntity;
    }

    /**
     * @return bool
     */
    public function isDisableExtension()
    {
        return $this->disableExtension;
    }

    /**
     * @param bool $option
     * @return AccountModel
     */
    public function setDisableExtension($option)
    {
        $this->disableExtension = $option;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisableClientPasswordAccess()
    {
        return $this->disableClientPasswordAccess;
    }

    /**
     * @return AccountModel
     */
    public function setDisableClientPasswordAccess($disable)
    {
        $this->disableClientPasswordAccess = $disable;

        return $this;
    }

    public function setOwner(?Owner $owner = null)
    {
        if (null !== $this->user && $this->user !== $owner->getUser()) {
            $this->useragents = new ArrayCollection();
        }
        $this->traitSetOwner($owner);
    }

    /**
     * @see Account::setBalanceWatchStartDate()
     */
    public function setBalanceWatchStartDate(?\DateTime $dateTime): self
    {
        $this->balanceWatchStartDate = $dateTime;

        return $this;
    }

    public function getBalanceWatchStartDate(): ?\DateTime
    {
        return $this->balanceWatchStartDate;
    }

    public function setBalanceWatch(bool $isBalanceWatch): self
    {
        $this->balanceWatch = $isBalanceWatch;

        return $this;
    }

    public function getBalanceWatch(): bool
    {
        return $this->balanceWatch;
    }

    /**
     * @param int $pointsSource
     * @see BalanceWatch::setPointsSource()
     */
    public function setPointsSource($pointsSource): self
    {
        $this->pointsSource = $pointsSource;

        return $this;
    }

    public function getPointsSource(): int
    {
        return (int) $this->pointsSource;
    }

    /**
     * @see BalanceWatch::setTransferFromProvider()
     */
    public function setTransferFromProvider(?Provider $transferProvider): self
    {
        $this->transferFromProvider = $transferProvider;

        return $this;
    }

    public function getTransferFromProvider(): ?Provider
    {
        return $this->transferFromProvider;
    }

    public function getSourceProgramRegion(): ?string
    {
        return $this->sourceProgramRegion;
    }

    public function setSourceProgramRegion(?string $sourceProgramRegion): self
    {
        $this->sourceProgramRegion = $sourceProgramRegion;

        return $this;
    }

    public function getTargetProgramRegion(): ?string
    {
        return $this->targetProgramRegion;
    }

    public function setTargetProgramRegion(?string $targetProgramRegion): self
    {
        $this->targetProgramRegion = $targetProgramRegion;

        return $this;
    }

    /**
     * @see BalanceWatch::setTransferRequestDate()
     */
    public function setTransferRequestDate(?\DateTime $transferRequestDate): self
    {
        $this->transferRequestDate = $transferRequestDate;

        return $this;
    }

    public function getTransferRequestDate(): ?\DateTime
    {
        return $this->transferRequestDate;
    }

    /**
     * @see BalanceWatch::setExpectedPoints()
     */
    public function setExpectedPoints(?int $expectedPoints): self
    {
        $this->expectedPoints = $expectedPoints;

        return $this;
    }

    public function getExpectedPoints(): ?int
    {
        return $this->expectedPoints;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCustomEliteLevel(): ?string
    {
        return $this->customEliteLevel;
    }

    public function setCustomEliteLevel(?string $eliteLevel): self
    {
        $this->customEliteLevel = $eliteLevel;

        return $this;
    }

    public function setDisableBackgroundUpdating(bool $disableBackgroundUpdating): AccountModel
    {
        $this->disableBackgroundUpdating = $disableBackgroundUpdating;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisableBackgroundUpdating()
    {
        return $this->disableBackgroundUpdating;
    }
}
