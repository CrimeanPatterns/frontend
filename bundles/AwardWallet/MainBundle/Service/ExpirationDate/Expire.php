<?php

namespace AwardWallet\MainBundle\Service\ExpirationDate;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Providercoupon;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI()
 */
class Expire
{
    public $Kind;
    public $TargetKind;
    public $ID;
    public $ParentID;
    public $ProviderID;
    public $ProviderKind;
    public $ChangeCount;
    public $Name;
    public $ShortName;
    public $Balance;
    public $Value;
    public $ExpirationDate;
    public $BlogIdsMileExpiration;
    public $UserID;
    public $UserName;
    public $Email;
    public $EmailVerified;
    public $EmailFamilyMemberAlert;
    public $uaEmail;
    public $uaSendEmails;
    public $Owner;
    public $AccountLevel;
    public $ShareCode;
    public $UserAgentID;
    public $Days;
    public $Months;
    public $ExpirationAutoSet;
    public $Login;
    public $Notes;
    public $CurrencyID;
    public $Currency;
    public $LastCheckDays;
    public $LastSuccessCheckDays;
    public $SuccessCheckDate;
    public $ErrorCode;
    public $ErrorMessage;
    public $SavePassword;
    public $CustomFields;
    public $TypeID;
    public $TypeName;

    public function __construct()
    {
        $this->format();
    }

    public function __toString(): string
    {
        $isPassport = $this->isExpiredPassport();

        return sprintf(
            '%s%s, target: %s, user name: %s, email: %s, %s: %d',
            $this->Kind,
            $this->ID,
            $this->TargetKind,
            $this->UserName,
            $this->Email,
            $isPassport ? 'months' : 'days',
            $isPassport ? $this->Months : $this->Days
        );
    }

    public function format(): void
    {
        $this->ID = (int) $this->ID;
        $this->UserID = (int) $this->UserID;
        $this->AccountLevel = (int) $this->AccountLevel;
        $this->Days = (int) $this->Days;
        $this->Months = (int) $this->Months;
        $this->EmailVerified = (int) $this->EmailVerified;
        $this->EmailFamilyMemberAlert = (int) $this->EmailFamilyMemberAlert;
        $this->uaSendEmails = (int) $this->uaSendEmails;

        if (isset($this->ProviderID)) {
            $this->ProviderID = (int) $this->ProviderID;
        }

        if (isset($this->UserAgentID)) {
            $this->UserAgentID = (int) $this->UserAgentID;
        }

        if (!empty($this->Notes)) {
            $this->Notes = htmlspecialchars($this->Notes, ENT_NOQUOTES);
        }

        if ($this->Kind === 'S' && $this->Login === 'C') {
            $this->Kind = 'D';
        }

        if (!empty($this->BlogIdsMileExpiration)) {
            $this->BlogIdsMileExpiration = it(explode(',', $this->BlogIdsMileExpiration))
                ->map(fn (string $id) => (int) trim($id))
                ->toArray();
        }

        if (!empty($this->TypeID) && empty($this->TypeName) && array_key_exists($this->TypeID, Providercoupon::TYPES)) {
            $this->TypeName = Providercoupon::TYPES[$this->TypeID];
        }
    }

    public function isExpiredPassport(): bool
    {
        return $this->Kind === 'P';
    }
}
