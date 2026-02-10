<?php

namespace AwardWallet\Tests\Acceptance\_steps;

class UserSteps extends \WebGuy
{
    public function register($email = null, $password = null, $fn = null, $ln = null)
    {
        $I = $this;
        $email = $email ?: \CommonUser::$user_email;
        $password = $password ?: \CommonUser::$user_password;
        $fn = $fn ?: \CommonUser::$user_firstname;
        $ln = $ln ?: \CommonUser::$user_lastname;
        $this->deleteIfExist($email);

        $I->amOnPage($I->grabService('router')->generate(\RegisterPage::$route));
        $I->click(\RegisterPage::$selector_button);
        $I->waitForElementVisible(\RegisterPage::$selector_popup, 10);
        $I->click(\RegisterPage::$selector_quickreg_button);
        $I->wait(10);
        $I->fillField(\RegisterPage::$selector_email, $email);
        $I->fillField(\RegisterPage::$selector_password, $password);
        $I->fillField(\RegisterPage::$selector_fn, $fn);
        $I->fillField(\RegisterPage::$selector_ln, $ln);
        $I->click(\RegisterPage::$selector_submit);
        $I->wait(10);
        $I->waitForElementVisible('//i[@class="icon-logout"]');
    }

    public function login($login, $password, $rememberMe = null, $otc = null)
    {
        $I = $this;
        $I->amOnPage('/');
        $I->click(['link' => 'Log in']);
        $I->see('Existing User');
        $I->fillField('User name', $login);
        $I->fillField('Password', $password);
        $I->wait(1);
        $I->click(['css' => '#login-button']);
        $I->waitForElementVisible('//a[@title="Logout"]');
    }

    public function mobileLogin($username, $password)
    {
        $I = $this;
        $I->amOnPage('/m/');
        $I->waitForElementVisible('//input[@name="login"]');
        $I->fillField('login', $username);
        $I->fillField('password', $password);
        $I->click(['xpath' => '//button[@type="submit"]']);
        $I->waitForElementVisible('//*[@class="icon-logout"]');
    }

    public function logout(): void
    {
        $I = $this;
        $I->comment('logout');
        $this->amOnPage("/security/logout");
        $this->waitForText("Register", 10);
    }

    public function delete($password, $user = null)
    {
        $I = $this;
        $I->comment('remove user');

        if ($user) {
            $I->amOnPage($I->grabService('router')->generate('aw_user_delete', ['_switch_user' => $user]));
        } else {
            $I->amOnPage($I->grabService('router')->generate('aw_user_delete'));
        }

        $I->wait(1);
        $I->fillField('Reason', 'Bye bye');
        $I->fillField('AwardWallet password', $password);
        $I->click(['css' => 'input[type=submit]']);
        $I->waitForText('Please confirm');
        $I->click('Ok');
        $I->waitForText('Your account was deleted');
    }

    public function deleteIfExist($email)
    {
        $I = $this;
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $I->grabService('doctrine')->getManager();
        $user = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['email' => $email]);

        if ($user) {
            $em->remove($user);
            $em->flush();
        }
    }
}
