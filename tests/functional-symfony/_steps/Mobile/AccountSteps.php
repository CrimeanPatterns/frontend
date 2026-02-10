<?php

namespace AwardWallet\Tests\FunctionalSymfony\_steps\Mobile;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;

class AccountSteps extends MobileApiAbstractSteps
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const TEST_PROVIDER_ID = 636;

    public const OPTION_GRAB_DATA = 1;

    protected static $routes = [
        'data' => '/data',
        'add' => '/provider/%s',
        'edit' => '/account/%s',
        'delete' => '/account/%s',
        'localpassword' => '/account/localpassword/%s',
        'extension' => '/account/autologin/%s/%d/1/1',
        'redirect' => '/account/autologin/redirect/proxy/%d',
    ];

    /**
     * Adds new account.
     *
     * @param int $providerId provider id
     * @param string $login acount login
     * @param string $password account password
     * @param array $fields additional fields to change
     * @param int $options
     * @return string id of created account
     * @throws \InvalidArgumentException
     */
    public function addAccount($providerId, $login, $password = null, array $fields = [], $options = self::OPTION_GRAB_DATA)
    {
        $I = $this;

        $I->wantTo('add new account');

        $formData = $I->loadAccountForm($url = self::getUrl('add', $providerId));
        $formData['login'] = $login;
        $formData['pass'] = $password;

        if (isset($formData['notrelated'])) {
            $formData['notrelated'] = true;
        }

        $formData = array_merge($formData, $fields);

        if (!isset($password)) {
            unset($formData['pass']);
        }

        $I->sendPOST($url, $formData);
        $I->seeResponseCodeIs(200);
        $accountId = $I->grabDataFromJsonResponse('account.ID');

        if (isset($formData['savepassword']) && SAVE_PASSWORD_LOCALLY == $formData['savepassword']) {
            $I->seeCookie('APv2-0');
            $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Pass' => '']);
        } elseif (isset($formData['pass'])) {
            /** @var PasswordEncryptor $encryptor */
            $encryptor = $I->grabService(PasswordEncryptor::class);
            $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Pass' => $encryptor->encrypt($formData['pass'])]);
        }

        if (self::OPTION_GRAB_DATA & $options) {
            $this->loadData();
            $I->grabDataFromJsonResponse("accounts.a{$accountId}.ID");
        }

        return $accountId;
    }

    /**
     * @return array
     */
    public function loadAccountForm($url)
    {
        $I = $this;
        $I->sendGET($url);
        $I->seeResponseCodeIs(200);

        return array_combine(
            $I->grabDataFromJsonResponse('formData.children.*.name'),
            $I->grabDataFromJsonResponse('formData.children.*.value')
        );
    }

    /**
     * Edits existing account.
     *
     * @param int $accountId  account id
     * @param string $login account login
     * @param string|array $password account password
     * @param array $fields additional fields to change
     */
    public function editAccount($accountId, $login, $password, array $fields = [], array $expectedErrors = [])
    {
        $I = $this;

        $I->wantTo('edit existing account');

        $lastPassword = $I->grabFromDatabase('Account', 'Pass', ['AccountID' => $accountId]);
        $lastLogin = $I->grabFromDatabase('Account', 'Login', ['AccountID' => $accountId]);

        $formData = $this->loadAccountForm($url = self::getUrl('edit', $accountId));

        if (is_array($password)) {
            $oldPassword = $password[0];
            $password = $password[1];

            if (SAVE_PASSWORD_DATABASE == $I->grabFromDatabase('Account', 'SavePassword', ['AccountID' => $accountId])) {
                /** @var PasswordDecryptor $decryptor */
                $decryptor = $I->grabService(PasswordDecryptor::class);
                $I->assertEquals($decryptor->decrypt($lastPassword), $oldPassword, 'expected and actual password mismatch');

                if (isset($formData['pass'])) {
                    $I->assertEquals(str_pad('', strlen($oldPassword), '*'), $formData['pass'], 'Password field should be masked by same-length replacement');
                }
            }
        }

        if (isset($login)) {
            $formData['login'] = $login;
        }

        if (isset($password)) {
            $formData['pass'] = $password;
        } else {
            unset($formData['pass']);
        }

        if (isset($formData['notrelated'])) {
            $formData['notrelated'] = true;
        }

        $postData = array_merge($formData, $fields);
        $I->sendPUT($url, $postData);
        $I->seeResponseCodeIs(200);

        if ($expectedErrors) {
            $this->checkFormErrors($expectedErrors);
            $I->seeInDatabase('Account', [
                'AccountID' => $accountId,
                'Login' => $lastLogin,
                'Pass' => $lastPassword,
            ]);

            return;
        }

        $I->assertEquals($accountId, (int) $I->grabDataFromJsonResponse('account.ID'));

        if (isset($formData['savepassword']) && isset($fields['savepassword']) && $fields['savepassword'] != $formData['savepassword']) {
            if (SAVE_PASSWORD_LOCALLY == $fields['savepassword']) {
                $I->seeCookie('APv2-0');
                $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Pass' => '']);
            } else {
                $I->assertNotEmpty($I->grabFromDatabase('Account', 'Pass', ['AccountID' => $accountId]), 'Local password was not restored to database');
            }
        } else {
            if (isset($postData['savepassword'])) {
                if (SAVE_PASSWORD_DATABASE == $postData['savepassword']) {
                    if (isset($postData['pass'])) {
                        /** @var PasswordEncryptor $encryptor */
                        $encryptor = $I->grabService(PasswordEncryptor::class);
                        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Pass' => $encryptor->encrypt($postData['pass'])]);
                    } else {
                        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Pass' => $lastPassword]);
                    }
                } else {
                    $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Pass' => '']);
                }
            }
        }

        if (isset($postData['owner']) && '' !== $postData['owner']) {
            [$user, $useragent] = $this->splitOwner($postData['owner']);

            if (isset($useragent)) {
                $checkCriteria['useragentid'] = $useragent;
            }
        }

        $checkCriteria = [
            'AccountID' => $accountId,
            'Login' => ($login ?? $lastLogin),
            'SavePassword' => $postData['savepassword'],
        ];
        $I->seeInDatabase('Account', $checkCriteria);

        if (isset($login)) {
            $I->seeResponseContainsJson(['account' => ['ID' => $accountId, 'Login' => $login]]);
        }
    }

    public function deleteAccount($accountId)
    {
        $I = $this;

        $I->wantTo('delete account');

        $I->sendDELETE(self::getUrl('delete', $accountId));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['accountId' => (int) $accountId]);
        $I->dontSeeInDatabase('Account', ['AccountID' => $accountId]);
        $this->loadData();
        $I->dontSeeDataInJsonResponse("accounts.a{$accountId}");
    }

    public function loadData()
    {
        $I = $this;
        $I->sendGET(self::getUrl('data'));
        $I->seeResponseCodeIs(200);
        $I->assertNotEmpty($etag = $I->grabHttpHeader('ETag'));
        $I->assertStringContainsString(hash('sha256', $I->grabResponse()), $etag);
        $I->seeResponseIsJson();

        return $this->grabDataFromJsonResponse('');
    }

    public function deleteLocalPasswords($name = 'APv2-0', $path = null, $domain = null)
    {
        // removing 0-indexed cookie part, so the remaining parts if they exist become invalid
        $I = $this;
        $I->wantTo('remove local passwords');
        $this->resetCookie($name, $path, $domain);
        $I->dontSeeCookie($name, $path, $domain);
    }

    public function setLocalPassword($accountId, $password)
    {
        $I = $this;
        $I->wantTo('provide local-stored password for account ' . $accountId);
        $I->sendGET($questionUrl = self::getUrl('localpassword', $accountId));
        $this->assertEquals('password', $I->grabDataFromJsonResponse('formData.children.0.name'));
        $I->sendPOST($questionUrl, ['password' => $password]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeCookie('APv2-0');
    }

    public function loadExtension($accountId, $version)
    {
        $I = $this;
        $I->sendGET(self::getUrl('extension', $version, $accountId));
    }

    protected function splitOwner(string $owner): array
    {
        $parts = explode('_', $owner);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }
}
