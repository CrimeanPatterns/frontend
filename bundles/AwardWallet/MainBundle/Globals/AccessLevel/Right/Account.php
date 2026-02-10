<?php

namespace AwardWallet\MainBundle\Globals\AccessLevel\Right;

use AwardWallet\MainBundle\Security\Utils;

class Account extends AbstractRight
{
    public function fetchFields($ids, $filter)
    {
        $connection = $this->em->getConnection();
        $sql = "
			SELECT
				   a.AccountID AS ID,
				   a.UserID,
			       t.*,
			       p.CanCheck,
			       p.State as ProviderState,
			       a.SavePassword,
			       a.ErrorCode,
			       a.ProviderID
			FROM   Account a
			       LEFT OUTER JOIN
			              ( SELECT sh.AccountID,
			                      ua.*
			              FROM    AccountShare sh
			                      JOIN UserAgent ua
			                      ON      sh.UserAgentID    = ua.UserAgentID
			                              AND ua.AgentID    = ?
			                              AND ua.IsApproved = 1
			              WHERE   sh.AccountID             IN (" . $filter . ")
			              )
			              t
			       ON     t.AccountID = a.AccountID
			       LEFT OUTER JOIN Provider p
			       ON 	  p.ProviderID = a.ProviderID
			WHERE  a.AccountID       IN (" . $filter . ")
		";
        $this->fields = $connection->executeQuery($sql,
            [$this->user->getUserid()],
            [\PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);

        // load password vault data
        $pvData = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Passwordvault::class)->getAccountsUsers($ids);

        foreach ($this->fields as &$accountData) {
            $accountId = $accountData['ID'];
            $accountData['PasswordVault'] = $pvData[$accountId] ?? ['Login' => [], 'UserID' => []];
        }
    }

    public function getAllPermissions()
    {
        return [
            'read_password', 'read_number',
            'read_balance', 'read_extprop',
            'edit', 'delete',
            'autologin', 'update', 'show_ext',
        ];
    }

    public function read_password()
    {
        $result = $this->getDefaultValues();

        // impersonate
        if ($this->isImpersonated() && !$this->isImpersonatedAdmin()) {
            return $result;
        }

        foreach ($this->fields as $fields) {
            $result[$fields['ID']] = $this->full_rights($fields);
        }

        return $result;
    }

    public function read_number()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $r = false;

            if ($this->user->getUserid() == $fields['UserID']) {
                $r = true;
            } else {
                if (!isset($fields['AccessLevel'])) {
                    $r = false;
                } else {
                    $r = in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL, ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_NUMBER]);
                }
            }

            $result[$fields['ID']] = $r;
        }

        return $result;
    }

    public function read_balance()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $r = false;

            if ($this->user->getUserid() == $fields['UserID']) {
                $r = true;
            } else {
                if (!isset($fields['AccessLevel'])) {
                    $r = false;
                } else {
                    $r = in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL, ACCESS_READ_BALANCE_AND_STATUS]);
                }
            }

            $result[$fields['ID']] = $r;
        }

        return $result;
    }

    public function read_extprop()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $r = false;

            if ($this->user->getUserid() == $fields['UserID']) {
                $r = true;
            } else {
                if (!isset($fields['AccessLevel'])) {
                    $r = false;
                } else {
                    $r = in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL]);
                }
            }

            $result[$fields['ID']] = $r;
        }

        return $result;
    }

    public function edit()
    {
        return $this->read_password();
    }

    public function delete()
    {
        return $this->read_password();
    }

    public function autologin()
    {
        return $this->read_password();
    }

    public function update()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $r = false;

            if ($this->user->getUserid() == $fields['UserID']) {
                $r = true;
            } else {
                if (!isset($fields['AccessLevel'])) {
                    $r = false;
                } else {
                    $r = in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_READ_ALL]);
                }
            }
            $r = ($r && $fields['CanCheck'] == '1');

            if (PROVIDER_CHECKING_EXTENSION_ONLY == $fields['ProviderState'] && $this->isImpersonated()) {
                $impersonator = Utils::getImpersonator(getSymfonyContainer()->get("security.token_storage")->getToken());
                $r = ($r && (
                    in_array($impersonator, $fields['PasswordVault']['Login'], true)
                    || in_array($impersonator, $fields['PasswordVault']['UserID'], true)
                )
                );
            }

            if (SAVE_PASSWORD_LOCALLY == $fields['SavePassword'] && !(AA_PROVIDER_ID == $fields['ProviderID'] && ACCOUNT_CHECKED == $fields['ErrorCode'])) {
                $r = ($r && !$this->isImpersonated());
            }

            $result[$fields['ID']] = $r;
        }

        return $result;
    }

    public function show_ext()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $r = false;

            if ($this->user->getUserid() == $fields['UserID']) {
                $r = true;
            } else {
                if (!isset($fields['AccessLevel'])) {
                    $r = false;
                } else {
                    $r = !in_array($fields['AccessLevel'], [ACCESS_READ_BALANCE_AND_STATUS]);
                }
            }

            $result[$fields['ID']] = $r;
        }

        return $result;
    }

    protected function full_rights($fields)
    {
        if ($this->user->getUserid() == $fields['UserID']) {
            return true;
        }

        if (!isset($fields['AccessLevel'])) {
            return false;
        }

        return in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]);
    }

    private function isImpersonatedAdmin()
    {
        return getSymfonyContainer()->get("security.authorization_checker")->isGranted('ROLE_IMPERSONATED_FULLY');
    }

    private function isImpersonated()
    {
        return getSymfonyContainer()->get("security.authorization_checker")->isGranted('ROLE_IMPERSONATED');
    }
}
