<?php

$bNoSession = true;
$sTitle = "Create a fake user";

require "../kernel/public.php";

require_once "../account/common.php";

require_once "$sPath/lib/classes/TBaseFormEngConstants.php";

require "$sPath/lib/admin/design/header.php";

function createFirstName()
{
    $fn = ['Isabella', 'Jacob', 'Sophia', 'Emma', 'Michael', 'Ethan', 'William', 'Alexander', 'Noah', 'Daniel', 'Liam', 'John', 'Jackson', 'Samuel', 'Joseph', 'James',
        'Elijah', 'Logan', 'Matthew', 'David', 'Andrew', 'Christopher', 'Mason', 'Joshua', 'Anthony', 'Aiden', 'Lucas', 'Evan', 'Gavin', 'Nicholas', 'Brandon', 'Carter', 'Justin',
        'Julian', 'Robert', 'Aaron', 'Kevin'];

    return $fn[array_rand($fn)];
}

function createMiddleName()
{
    $mn = ['Joy', 'Hope', 'Faith', 'Noelle', 'Grace', 'Nicole'];

    return $mn[array_rand($mn)];
}

function createLastName()
{
    $ln = ['Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'White', 'Harris', 'Martin', 'Thompson',
        'Garcia', 'Martinez', 'Robinson', 'Clark', 'Rodriguez', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'Hernandez', 'King', 'Wright', 'Lopez', 'Hill', 'Scott', 'Green',
        'Adams', 'Baker', 'Gonzalez', 'Nelson', 'Carter', 'Perez', 'Roberts', 'Turner', 'Phillips', 'Campbell', 'Parker', 'Stewart', 'Howard', 'Watson'];

    return $ln[array_rand($ln)];
}

function getRandAccounts($count)
{
    $sql = "SELECT Account.* FROM Account 
			JOIN ( 
				SELECT CEIL(RAND() * 
					( SELECT MAX(AccountID) FROM Account )
				) AS randomID ) AS random_table 
			ON Account.AccountID>=random_table.randomID LIMIT " . $count . "";

    return SQLToArray($sql, "ProviderID", "UserID", true);
}

$objForm = new TBaseForm([
    "Login" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Login",
        "RegExp" => "/^[a-z0-9]{3,25}$/ims",
        "Note" => "(3-25 char)",
        "Value" => "",
        "Required" => true,
    ],
    "Password" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Password",
        "RegExp" => "/^[a-z0-9]{6,35}$/ims",
        "Note" => "(6-35 char)",
        "Value" => "",
        "Required" => true,
    ],
    "BusinessAccount" => [
        "Type" => "integer",
        "InputType" => "checkbox",
        "Caption" => "Business Account",
        "Value" => true,
        "Required" => true,
    ],
    "NumberFamilyMembers" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Number of family members",
        "RegExp" => "/^\d+$/ims",
        "Value" => "5",
        "Required" => true,
    ],
    "NumberRealPerson" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Number of real person",
        "RegExp" => "/^\d+$/ims",
        "Value" => "0",
        "Required" => true,
    ],
    "NumberLPOwner" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Number of LP (owner)",
        "RegExp" => "/^\d+$/ims",
        "Value" => "0",
        "Required" => true,
    ],
    "NumberLPFmember" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Number of LP (family member)",
        "RegExp" => "/^\d+$/ims",
        "Value" => "0",
        "Required" => true,
    ],
    "NumberLPContacts" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Number of LP (contacts)",
        "RegExp" => "/^\d+$/ims",
        "Value" => "0",
        "Required" => true,
        "Note" => "Only real users (not family members)",
    ],
    "Company" => [
        "Type" => "string",
        "Size" => 60,
        "Caption" => "Company",
        "RegExp" => "/^[a-z0-9\ ]{3,50}$/ims",
        "Value" => "Company " . mt_rand(1, 10000) . "",
        "Required" => true,
    ],
    "BusinessAccountType" => [
        "Type" => "integer",
        "Note" => "For Business",
        "InputType" => "select",
        "Caption" => "Business Account Type",
        "Options" => [
            0 => "Trial Period",
            CART_ITEM_AWB => "Regular Business",
            CART_ITEM_AWB_PLUS => "Business Plus",
        ],
        "Required" => true,
    ],
    "DateSubscription" => [
        "Type" => "date",
        "Note" => "For Business",
        "Caption" => "Date of subscription",
        "Value" => date("m/d/Y", time() - 3600 * 24 * 30),
        "Required" => true,
    ],
    "Discount" => [
        "Type" => "integer",
        "Note" => "For Business",
        "Caption" => "Discount (0-100)",
        "RegExp" => "/^\d+$/ims",
        "Value" => 0,
        "Required" => true,
    ],
    "Balance" => [
        "Type" => "float",
        "Note" => "For Business",
        "Caption" => "Balance (from a previous subscription)",
        "RegExp" => "/^[\d\.]+$/ims",
        "Value" => 0,
        "Required" => true,
    ],
    "DirectPayment" => [
        "Type" => "integer",
        "InputType" => "checkbox",
        "Caption" => "Direct Payment",
        "Value" => false,
        "Required" => true,
    ],
]);
$objForm->SubmitButtonCaption = "Create a fake user";

if ($objForm->IsPost && $objForm->Check()) {
    try {
        $query = new TQuery("select * from Usr where Login = '" . addslashes($objForm->Fields['Login']['Value']) . "'");

        if (!$query->EOF) {
            throw new Exception("User with this login already exists!");
        }

        if ($objForm->Fields['NumberLPFmember']['Value'] > $objForm->Fields['NumberLPOwner']['Value']) {
            throw new Exception("Number of LP family member can not exceed number of Owner!");
        }

        $insertRow = [
            'Login' => "'" . $objForm->Fields['Login']['Value'] . "'",
            'Pass' => "'" . getSymfonyPasswordEncoder()->encodePassword($objForm->Fields['Password']['Value'], null) . "'",
            'FirstName' => "'" . createFirstName() . "'",
            'MidName' => "'" . createMiddleName() . "'",
            'LastName' => "'" . createLastName() . "'",
            'Company' => "'" . $objForm->Fields['Company']['Value'] . "'",
            'CreationDateTime' => "'" . date('Y-m-d H:i:s') . "'",
            'Email' => "'Email" . mt_rand(1, 999999) . "@mail.ru'",
            'EmailVerified' => "1",
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
            'LockoutStart' => 'null',
        ];
        $Connection->Execute(InsertSQL("Usr", $insertRow));
        $uid = $Connection->InsertID();
        $userRepo = getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        if ($objForm->Fields['BusinessAccount']['Value'] == 1) {
            $insertRow = [
                'Login' => "'" . addslashes($userRepo->createLogin(0, $objForm->Fields['Company']['Value'])) . "'",
                'Pass' => "'disabled'",
                'FirstName' => "'Business'",
                'LastName' => "'Account'",
                'Company' => "'" . $objForm->Fields['Company']['Value'] . "'",
                'CreationDateTime' => "'" . date('Y-m-d H:i:s') . "'",
                'Email' => "'b." . $uid . "@awardwallet.com'",
                'EmailVerified' => "1",
                'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
                'LockoutStart' => "null",
                'RefCode' => "'" . addslashes(RandomStr(ord('a'), ord('z'), 10)) . "'",
            ];
            $Connection->Execute(InsertSQL("Usr", $insertRow));
            $bid = $Connection->InsertID();
            $Connection->Execute(InsertSQL("UserAgent", [
                'AgentID' => "'" . $uid . "'",
                'ClientID' => "'" . $bid . "'",
                'AccessLevel' => "'" . ACCESS_ADMIN . "'",
                'IsApproved' => 1,
            ]));
            $Connection->Execute(InsertSQL("UserAgent", [
                'AgentID' => "'" . $bid . "'",
                'ClientID' => "'" . $uid . "'",
                'AccessLevel' => "'" . ACCESS_WRITE . "'",
                'IsApproved' => 1,
            ]));
        }

        if ($objForm->Fields['NumberFamilyMembers']['Value'] > 0) {
            for ($i = 0; $i < $objForm->Fields['NumberFamilyMembers']['Value']; $i++) {
                $Connection->Execute(InsertSQL("UserAgent", [
                    'AgentID' => "'" . ($bid ?? $uid) . "'",
                    'ClientID' => "null",
                    'FirstName' => "'" . createFirstName() . "'",
                    'LastName' => "'" . createLastName() . "'",
                    'Email' => "'fake-" . mt_rand(1, 9999) . "@awardwallet.com'",
                    'AccessLevel' => "'" . ACCESS_WRITE . "'",
                    'IsApproved' => "1",
                ]));
                $familyUsers[] = $Connection->InsertID();
            }
        }

        // Add to the owner of the LP
        if ($objForm->Fields['NumberLPOwner']['Value'] > 0) {
            $accounts = getRandAccounts($objForm->Fields['NumberLPOwner']['Value']);

            foreach ($accounts as $i => $account) {
                unset($account['AccountID'], $account['Goal'], $account['BrowserState'], $account['UserAgentID'], $account['UpdateDate'],
                    $account['GoalAutoSet'], $account['PassChangeDate']);
                $account['UserID'] = (isset($bid)) ? $bid : $uid;
                $account['Login'] = $account['Login'] . $i;
                $account['Pass'] = (trim($account['Pass']) == '') ? rand(1, 999999) : $account['Pass'];
                $account['UpdateDate'] = date("Y-m-d H:i:s");
                $account['SubAccounts'] = 0;

                // Account for family members
                if ($objForm->Fields['NumberLPFmember']['Value'] > 0 && isset($familyUsers)) {
                    $account['UserAgentID'] = $familyUsers[mt_rand(0, count($familyUsers) - 1)];
                    $objForm->Fields['NumberLPFmember']['Value']--;
                }

                foreach ($account as $k => $v) {
                    $account[$k] = "'" . addslashes($v) . "'";
                }

                $Connection->Execute(InsertSQL("Account", $account));
            }
        }

        if ($objForm->Fields['NumberRealPerson']['Value'] > 0) {
            $tableAddContacts = [];

            for ($i = 0; $i < $objForm->Fields['NumberRealPerson']['Value']; $i++) {
                while (true) {
                    $_login = 'Login' . RandomStr(ord('a'), ord('z'), 15);
                    $query = new TQuery("select * from Usr where Login = '{$_login}'");

                    if ($query->EOF) {
                        break;
                    }
                }
                $_pass = $objForm->Fields['Password']['Value'];
                $_company = 'Company ' . RandomStr(ord('a'), ord('z'), 3);
                $tableAddContacts[$i]['Login'] = $_login;
                $tableAddContacts[$i]['Pass'] = $_pass;
                $tableAddContacts[$i]['Company'] = $_company;
                $fn = createFirstName();
                $mn = createMiddleName();
                $ln = createLastName();
                $email = 'Email' . mt_rand(1, 999999) . '@mail.ru';
                $insertRow = [
                    'Login' => "'" . $_login . "'",
                    'Pass' => "'" . getSymfonyPasswordEncoder()->encodePassword($_pass, null) . "'",
                    'FirstName' => "'" . $fn . "'",
                    'MidName' => "'" . $mn . "'",
                    'LastName' => "'" . $ln . "'",
                    'Company' => "'" . $_company . "'",
                    'Email' => "'" . $email . "'",
                    'EmailVerified' => "1",
                    'AccountLevel' => "1",
                    'LockoutStart' => 'null',
                ];
                $Connection->Execute(InsertSQL("Usr", $insertRow));
                $_uid = $Connection->InsertID();

                if (isset($bid)) {
                    $owner = $bid;
                    $access1 = ACCESS_ADMIN;
                    $access2 = ACCESS_NONE;
                } else {
                    $owner = $uid;
                    $access1 = ACCESS_WRITE;
                    $access2 = ACCESS_WRITE;
                }
                $Connection->Execute(InsertSQL("UserAgent", [
                    'AgentID' => "'" . $owner . "'",
                    'ClientID' => "'" . $_uid . "'",
                    'AccessLevel' => "'" . $access1 . "'",
                    'IsApproved' => "1",
                ]));
                $userAgentID = $Connection->InsertID();
                $Connection->Execute(InsertSQL("UserAgent", [
                    'AgentID' => "'" . $_uid . "'",
                    'ClientID' => "'" . $owner . "'",
                    'AccessLevel' => "'" . $access2 . "'",
                    'IsApproved' => "1",
                ]));

                if ($objForm->Fields['NumberLPContacts']['Value'] > 0) {
                    $accounts = getRandAccounts($objForm->Fields['NumberLPContacts']['Value']);

                    foreach ($accounts as $z => $account) {
                        unset($account['AccountID'], $account['Goal'], $account['BrowserState'], $account['UserAgentID'], $account['UpdateDate'],
                            $account['GoalAutoSet'], $account['PassChangeDate']);
                        $account['UserID'] = $_uid;
                        $account['Login'] = $account['Login'] . $z;
                        $account['Pass'] = (trim($account['Pass']) == '') ? rand(1, 999999) : $account['Pass'];
                        $account['UpdateDate'] = date("Y-m-d H:i:s");
                        $account['SubAccounts'] = 0;

                        foreach ($account as $k => $v) {
                            $account[$k] = "'" . addslashes($v) . "'";
                        }

                        $Connection->Execute(InsertSQL("Account", $account));
                        $accountID = $Connection->InsertID();
                        $Connection->Execute(InsertSQL("AccountShare", ['AccountID' => $accountID, 'UserAgentID' => $userAgentID]));
                    }
                }
            }
        }

        if ($objForm->Fields['BusinessAccount']['Value'] == 1
            && in_array($objForm->Fields['BusinessAccountType']['Value'], [0, CART_ITEM_AWB, CART_ITEM_AWB_PLUS])
            && strtotime($objForm->Fields['DateSubscription']['Value']) !== false) {
            $tariff = ($objForm->Fields['BusinessAccountType']['Value'] == 0) ? CART_ITEM_AWB : $objForm->Fields['BusinessAccountType']['Value'];
            $dateSubscription = strtotime($objForm->Fields['DateSubscription']['Value']);
        }

        if (isset($tableAddContacts) && sizeof($tableAddContacts) > 0) {
            echo "Created fake users: <br />";

            foreach ($tableAddContacts as $index => $row) {
                echo ($index + 1) . ". " . $row['Login'] . " / " . $row['Pass'] . "<br />";
            }
            echo "<br />";
        }

        echo "<strong>User created!</strong><br />";
    } catch (Exception $e) {
        echo "<strong>" . $e->getMessage() . "</strong><br /><br />";
    }
}

if ($objForm->Error) {
    $Interface->DrawMessage($objForm->Error, "warning");
}

echo '
<style type="text/css">
td {
	vertical-align: top;
}
table#fakeUser input, table#fakeUser select {
	margin: 3px 1px;
	padding: 3px 5px;
	font-size: 14px;
	color: #585858;
}
</style>
<form method="post"  enctype="multipart/form-data" name="editor_form" style="margin-bottom: 0px; margin-top: 0px;" onsubmit="submitonce(this)">
<input type="hidden" name="FormToken" value="' . GetFormToken() . '">
<table border="0" id="fakeUser" style="margin: 3px;" cellpadding="2" cellspacing="1">
	<tr>
		<td width="250">
			<fieldset style="background-color: #FFE3DD;" id="fake_owner">
				<legend>Owner</legend>
				Login (3-25 char): <br />
				' . $objForm->InputHTML('Login') . '<br />
				Password (6-35 char):<br />
				' . $objForm->InputHTML('Password') . '<br />
				' . $objForm->InputHTML('BusinessAccount') . ' Business Account<br />
				Company:<br />
				' . $objForm->InputHTML('Company') . '<br />
				Number of LP:<br />
				' . $objForm->InputHTML('NumberLPOwner') . '<br />
			</fieldset>
			
			<fieldset style="background-color: #E9E9E9;" id="fake_fm">
				<legend>Family members</legend>
				Number of family members:<br />
				' . $objForm->InputHTML('NumberFamilyMembers') . '<br />
				Number of LP:<br />
				' . $objForm->InputHTML('NumberLPFmember') . '<br />
			</fieldset>
			
			<fieldset style="background-color: #D7F0FB;" id="fake_rp">
				<legend>Real persons</legend>
				Number of real person:<br />
				' . $objForm->InputHTML('NumberRealPerson') . '<br />
				Number of LP:<br />
				' . $objForm->InputHTML('NumberLPContacts') . '<br />
			</fieldset>
		</td>
		<td width="250">
			<fieldset style="background-color: #DFFDDB;" id="fake_business">
				<legend>Business options</legend>
				Business Account Type:<br />
				' . $objForm->InputHTML('BusinessAccountType') . '<br />
				Date of subscription:<br />
				' . $objForm->InputHTML('DateSubscription') . '<br />
				Discount (0-100):<br />
				' . $objForm->InputHTML('Discount') . '<br />
				Balance (previous subscription):<br />
				' . $objForm->InputHTML('Balance') . '<br />
				' . $objForm->InputHTML('DirectPayment') . ' Direct Payment<br />
			</fieldset>
		</td>
	</tr>
</table>
' . $objForm->HiddenInputs() . '
' . $objForm->DrawButton($objForm->SubmitButtonCaption, ' onclick="if( CheckForm( document.forms[\'editor_form\'] ) ) { this.form.submitButton.value=\'submit\'; return true; } else return false;"') . '
</form>
';
echo $objForm->CheckScripts();

require "$sPath/lib/admin/design/footer.php";

echo <<<HTML
<script type="text/javascript">
$(document).ready(function() {
	init_fu();
	$('input[name="BusinessAccount"]').click(init_fu);
});

function init_fu() {
	if ($('input[name="BusinessAccount"]').is(':checked') == true) {
		$('#fake_business input, #fake_business select').removeAttr('disabled');
		$('#fake_business').css({'background-color': '#DFFDDB'});
	} else {
		$('#fake_business input, #fake_business select').attr('disabled', 'disabled');
		$('#fake_business').css({'background-color': '#FFFFFF'});
	}
}
</script>
HTML;
