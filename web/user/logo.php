<?php

require "../kernel/public.php";

$userID = intval(ArrayVal($_GET, 'ID', 0));
$hash = ArrayVal($_GET, 'hash', null);

AuthorizeUser(false);

if ((isset($_SESSION['UserID']) && $_SESSION['UserID'] == $userID) || !isset($_SESSION['UserID'])) {
    /** @var \AwardWallet\MainBundle\Entity\Usr $user */
    $user = getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userID);

    if ($user && isset($hash) && $hash == $user->getEmailVerificationHash()) {
        $Connection->Execute(UpdateSQL('Usr', ['UserID' => $userID], ['EmailVerified' => EMAIL_VERIFIED, 'LastEmailReadDate' => 'NOW()']));
        $email = Lookup('Usr', 'UserID', 'Email', "'{$userID}'");
        //		$Connection->Execute(UpdateSQL('EmailNDR', array('Address'=>"'".$email."'"), array('Cnt'=>0)));
    }
}

header("Content-type: image/jpeg");

switch (ArrayVal($_GET, 'type')) {
    case 'booking':
        readfile($sPath . "/images/logoBookYourAward.png");

        break;

    case 'newdesign':
        readfile($sPath . "/images/email/design/email-logo-newdesign.png");

        break;

    default:
        readfile($sPath . "/images/email/design/email-logo.jpg");
}
