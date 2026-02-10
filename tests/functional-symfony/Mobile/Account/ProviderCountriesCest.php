<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\LegacyCommonCheckAccountFactoryService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertMatchesRegularExpression;

/**
 * @group mobile
 * @group frontend-functional
 */
class ProviderCountriesCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $countries = [];

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'prvctrs-', 'userpass-', [], true);

        foreach (['Z%', 'Z$'] as $countryCode) {
            $countryId = $I->haveInDatabase('Country', $data = [
                'Name' => 'Country ' . StringUtils::getRandomCode(10),
                'HaveStates' => 0,
                'Code' => $countryCode,
            ]);
            $this->countries[$countryCode] = array_merge($data, ['CountryID' => $countryId]);
        }
    }

    public function testCountryShouldBeSavedAsLogin2AccountField(\TestSymfonyGuy $I)
    {
        $countries = [
            'Z%' => [
                'LoginURL' => 'https://google.us',
                'Site' => 'https://google.us',
                'Name' => $this->countries['Z%']['Name'],
            ],
            'Z$' => [
                'LoginURL' => 'https://google.uk',
                'Site' => 'https://google.uk',
                'Name' => $this->countries['Z$']['Name'],
            ],
        ];

        // check form
        $providerId = $I->createAwProvider(
            'prvctrs' . StringUtils::getRandomCode(10),
            'prvctrs' . StringUtils::getRandomCode(10),
            [
                'Countries' => $countries,
                'Login2Caption' => 'Country',
                'Login2AsCountry' => true,
                'LoginCaption' => '', // loginless account
                'PasswordRequired' => false,
                'AutoLogin' => AUTOLOGIN_DISABLED,
                'LoginURL' => 'https://google.com',
            ]
        );
        $I->sendGET($url = AccountSteps::getUrl('add', $providerId));
        $choices = $I->grabDataFromResponseByJsonPath('$.formData.children..[?(@.name="login2")].choices[*].label');
        assertEquals(
            [
                'Select a region',
                $this->countries['Z%']['Name'],
                $this->countries['Z$']['Name'],
            ],
            $choices
        );

        // post form
        $form = array_combine(
            $I->grabDataFromResponseByJsonPath('$.formData.children[*].name'),
            $I->grabDataFromResponseByJsonPath('$.formData.children[*].value')
        );
        $form['notrelated'] = true;
        $form['login2'] = $countryId = $I->grabDataFromResponseByJsonPathList(
            '$.formData',
            '.children[?(@.name="login2")]',
            sprintf('.choices[?(@.label="%s")]', $this->countries['Z%']['Name']),
            '.value'
        )[0];
        $I->sendPOST($url, $form);

        // check saved account
        $I->seeResponseContainsJson(['account' => [
            'ProviderID' => $providerId,
        ]]);
        $I->seeInDatabase(
            'Account',
            [
                'AccountID' => $accountId = $I->grabDataFromResponseByJsonPath('account.ID')[0],
                'Login2' => $this->countries['Z%']['CountryID'],
            ]
        );
        $I->sendGET(AccountSteps::getUrl('edit', $accountId));
        assertEquals(
            $this->countries['Z%']['CountryID'],
            $I->grabDataFromResponseByJsonPathList(
                '$.formData',
                '.children[?(@.name="login2")]',
                '.value'
            )[0]
        );
        $this->accountSteps->loadData();
        $I->seeResponseContainsJson(['accounts' => [
            "a{$accountId}" => [
                'Autologin' => [
                    'loginUrl' => 'https://google.us',
                ],
            ],
        ]]);
    }

    public function testAutologinIsPointingToCountrySpecificSite(\TestSymfonyGuy $I)
    {
        $countries = [
            'Z%' => [
                'LoginURL' => 'https://google.us',
                'Site' => 'https://google.us',
                'Name' => $this->countries['Z%']['Name'],
            ],
            'Z$' => [
                'LoginURL' => 'https://google.uk',
                'Site' => 'https://google.uk',
                'Name' => $this->countries['Z$']['Name'],
            ],
        ];
        $I->mockService(
            'aw.legacy_common_check_account_factory',
            new class() extends LegacyCommonCheckAccountFactoryService {
                public function getAutologinFrame($accountId, $successUrl = null)
                {
                    throw new \Exception('No parsers available');
                }
            }
        );

        // check form
        $providerId = $I->createAwProvider(
            'prvctrs' . StringUtils::getRandomCode(10),
            'prvctrs' . StringUtils::getRandomCode(10),
            [
                'Countries' => $countries,
                'Login2Caption' => 'Country',
                'LoginCaption' => '', // loginless account
                'PasswordRequired' => false,
                'AutoLogin' => AUTOLOGIN_SERVER,
            ]
        );
        $accountId = $I->createAwAccount($this->userId, $providerId, '', '', ['Login2' => $this->countries['Z%']['CountryID']]);
        $this->accountSteps->loadData();
        assertMatchesRegularExpression(
            '#\/account\/redirect\?ID=' . $accountId . '#',
            $I->grabDataFromResponseByJsonPath("$.accounts.a{$accountId}.Autologin.loginUrl")[0]
        );

        $I->seeResponseContainsJson(['accounts' => ["a{$accountId}" => [
            'Autologin' => [
                'desktopExtension' => false,
                'mobileExtension' => false,
            ],
        ]]]);
        $I->sendGET('/account/redirect?ID=' . $accountId);
        //        $I->sendGET('/account/redirectProxy.php?ID=' . $accountId);
        //        $I->seeResponseContains('https://google.us');
    }
}
