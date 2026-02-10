<?php

namespace AwardWallet\MainBundle\Service\Backup\Processors;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Backup\BackupCommand;
use AwardWallet\MainBundle\Service\Backup\BackupProcessorInterface;
use AwardWallet\MainBundle\Service\Backup\PasswordEncoder;
use AwardWallet\MainBundle\Service\Backup\ProcessorInterestInterface;
use AwardWallet\MainBundle\Service\Backup\Users;

class CleanProcessor implements BackupProcessorInterface
{
    private PasswordEncoder $passwordEncoder;

    private Users $users;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(PasswordEncoder $passwordEncoder, Users $users, PasswordDecryptor $passwordDecryptor)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->users = $users;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    public function register(ProcessorInterestInterface $processorInterest): void
    {
        $password = $this->passwordEncoder->encodePassword(BackupCommand::DEVELOPER_PASSWORD);
        $salt = StringUtils::getRandomCode(20);
        $excludeUsers = $this->users->getExcludeUsers();

        $processorInterest
            ->addOnExportRow('AbBookerInfo', function (array $row) {
                $row['SmtpServer'] = 'mail';
                $row['SmtpPort'] = 25;
                $row['SmtpUseSsl'] = 0;
                $row['SmtpUsername'] = null;
                $row['SmtpPassword'] = null;
                $row['ImapPassword'] = null;
                $row['PayPalPassword'] = null;
                $row['PayPalClientId'] = null;
                $row['PayPalSecret'] = null;

                return $row;
            })
            ->addOnExportRow('AbRequest', function (array $row) {
                $row['ContactEmail'] = 'abr.' . $row['AbRequestID'] . '@fakemail.com';

                return $row;
            })
            ->addOnExportRow('Account', function (array $row) {
                if (in_array($row['UserID'], BackupCommand::KEEP_PASSWORD_USERS)) {
                    $row['Pass'] = $this->passwordDecryptor->decrypt($row['Pass']);
                } elseif (!empty($row['Pass'])) {
                    $row['Pass'] = '-';
                    $row['BrowserState'] = null;
                }

                if (!empty($row['AuthInfo'])) {
                    $row['AuthInfo'] = '-';
                }

                return $row;
            })
            ->addJoin('AccountProperty', 'join ProviderProperty on ProviderProperty.ProviderPropertyID = AccountProperty.ProviderPropertyID join Account on AccountProperty.AccountID = Account.AccountID')
            ->addExtraColumns('AccountProperty', 'ProviderProperty.Code, Account.ProviderID')
            ->addOnExportRow('AccountProperty', function (array $row) {
                if ($row['Code'] === 'Email') {
                    $row['Val'] = 'hidden' . $row['AccountPropertyID'] . '@hidden.com';
                }

                return $row;
            })
            ->addOnExportRow('BillingAddress', function (array $row) use ($excludeUsers) {
                if (!in_array($row['UserID'], $excludeUsers)) {
                    if (!empty($row['AddressName'])) {
                        $row['AddressName'] = 'Hidden ' . $row['BillingAddressID'];
                    }

                    if (!empty($row['Address1'])) {
                        $row['Address1'] = 'Hidden street';
                    }

                    if (!empty($row['Address2'])) {
                        $row['Address2'] = 'Hidden';
                    }

                    if (!empty($row['Zip'])) {
                        $row['Zip'] = null;
                    }
                }

                return $row;
            })
            ->addOnExportRow('Cart', function (array $row) use ($excludeUsers) {
                if (!empty($row['BillFirstName'])) {
                    $row['BillFirstName'] = 'John';
                }

                if (!empty($row['BillLastName'])) {
                    $row['BillLastName'] = 'Smith';
                }

                if (!empty($row['BillAddress1'])) {
                    $row['BillAddress1'] = 'Hidden';
                }

                if (!empty($row['BillAddress2'])) {
                    $row['BillAddress2'] = 'Hidden';
                }
                $row['BillCity'] = null;
                $row['BillZip'] = null;

                if (!in_array($row['UserID'], $excludeUsers)) {
                    $row['Email'] = 'user' . $row['UserID'] . '@fakemail.com';
                }

                if (!empty($row['BillingTransactionID'])) {
                    $row['BillingTransactionID'] = 'hidden' . $row['CartID'];
                }

                if (!empty($row['CreditCardNumber'])) {
                    $row['CreditCardNumber'] = 'XXXXXXXXXXXX1234';
                }

                if (!empty($row['CouponCode']) && !in_array($row['UserID'], $excludeUsers)) {
                    $row['CouponCode'] = 'hidden';
                }

                return $row;
            })
            ->addOnExportRow('Coupon', function (array $row) {
                if ($row['Name'] !== 'Invite bonus') {
                    $row['Code'] = 'hidden' . $row['CouponID'];
                }

                return $row;
            })
            ->addJoin('EmailNDR', 'left join Usr on Usr.Email = EmailNDR.Address')
            ->addExtraColumns('EmailNDR', 'Usr.UserID')
            ->addOnExportRow('EmailNDR', function (array $row) use ($excludeUsers) {
                if (!empty($row['UserID'])) {
                    if (!in_array($row['UserID'], $excludeUsers)) {
                        $row['Address'] = 'user' . $row['UserID'] . '@fakemail.com';
                    }
                } else {
                    $row['Address'] = 'hidden' . $row['EmailNDRID'] . '@fakemail.com';
                }

                return $row;
            })
            ->addOnExportRow('Invites', function (array $row) use ($excludeUsers) {
                if (!in_array($row['InviterID'], $excludeUsers)) {
                    $row['Email'] = 'invites' . $row['InvitesID'] . '@fakemail.com';
                }

                return $row;
            })
            ->addOnExportRow('MobileDevice', function (array $row) {
                $row['DeviceKey'] = 'som3-d3vic3-k3y-' . $row['MobileDeviceID'];

                return $row;
            })
            ->addOnExportRow('OA2Client', function (array $row) {
                $row['Pass'] = BackupCommand::DEVELOPER_PASSWORD;

                if ($row['Login'] === 'test') {
                    $row['RedirectURL'] = 'http://awardwallet.docker/api/oauth2/testCode.php';
                }

                return $row;
            })
            ->addOnExportRow('OA2Code', function (array $row) {
                $row['Code'] = 'hid' . $row['UserID'] . md5($row['Code']);

                return $row;
            })
            ->addOnExportRow('Provider', function (array $row) {
                if ($row['State'] == PROVIDER_IN_DEVELOPMENT) {
                    $row['State'] = PROVIDER_ENABLED;
                }

                return $row;
            })
            ->addOnExportRow('RememberMeToken', function (array $row) {
                $row['Series'] = 'hidden' . $row['RememberMeTokenID'];
                $row['Token'] = 'hidden' . $row['RememberMeTokenID'];

                return $row;
            })
            ->addOnExportRow('ShippingAddress', function (array $row) use ($excludeUsers) {
                if (!in_array($row['UserID'], $excludeUsers)) {
                    if (!empty($row['AddressName'])) {
                        $row['AddressName'] = 'Hidden ' . $row['ShippingAddressID'];
                    }

                    if (!empty($row['Address1'])) {
                        $row['Address1'] = 'Hidden street';
                    }

                    if (!empty($row['Address2'])) {
                        $row['Address2'] = 'Hidden';
                    }

                    if (!empty($row['Zip'])) {
                        $row['Zip'] = null;
                    }
                }

                return $row;
            })
            ->addOnExportRow('Session', function (array $row) use ($salt) {
                $row['SessionID'] = sha1($row['SessionID'] . $salt);

                return $row;
            })
            ->addOnExportRow('Usr', function (array $row) use ($password, $excludeUsers) {
                $row['Pass'] = $password;
                $row['GoogleAuthSecret'] = null;
                $row['GoogleAuthRecoveryCode'] = null;

                if (!in_array($row['UserID'], $excludeUsers) && !preg_match('#@awardwallet\.com$#ims', $row['Email'])) {
                    $row['Email'] = 'user' . $row['UserID'] . '@fakemail.com';
                    $row['City'] = null;
                    $row['Address1'] = null;
                    $row['Address2'] = null;
                    $row['Zip'] = null;
                } else {
                    $row['LastLogonIP'] = null;
                    $row['RegistrationIP'] = null;
                }

                return $row;
            });
    }
}
