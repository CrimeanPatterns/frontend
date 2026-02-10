<?php

namespace AwardWallet\MainBundle\Globals\Updater\Engine;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;

interface UpdaterEngineInterface
{
    public const OPTION_IT_AUTO = 1;

    public const SOURCE_MIN = 1;

    public const SOURCE_DESKTOP = 1;
    public const SOURCE_MOBILE = 2;
    public const SOURCE_BACKGROUND = 3;
    public const SOURCE_OPERATIONS = 4;

    public const SOURCE_MAX = 4;

    /**
     * $accounts = [
     * 	 	[ merged rows from Usr, Provider, Account tables + 'ParseItineraries' boolean field, for example: ],
     * 	 	[ 'AccountID' => 5, 'Code' => 'hhonors', 'AutoGatherPlans' => '1', 'ParseItineraries' => true ],
     * ].
     *
     * @param int $options
     * @param int $source - one of SOURCE_ constants
     * @return CheckAccountResponse[]
     */
    public function sendAccounts(array $accounts, $options = 0, $source = null);

    public function getUpdateSlots(Usr $user);

    public function getLogs($partner, $accountId, $providerCode = null, $login = null, $login2 = null, $login3 = null);

    public function retrieveConfirmation(array $fields, Provider $provider, array &$trips, Usr $user, ?Useragent $familyMember = null);

    public function getRedirectFrameUrl(?Account $account = null, Usr $user, ?Provider $provider = null);

    public function changePassword(Account $account);

    public function getCheckStrategy();

    public function getProviderInfo(string $code): ProviderInfoResponse;
}
