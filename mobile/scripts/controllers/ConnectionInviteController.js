angular.module('AwardWalletMobile').controller('ConnectionInviteController', [
    'Translator',
    function (Translator) {
        var translations = [
            Translator.trans(/** @Desc("This invitation has expired. Please contact %bold_on%%name%%bold_off% and request the invitation to be re-sent.")*/ 'invitation.expired'),
            Translator.trans(/** @Desc("%bold_on%%name%%bold_off% is inviting you to register on %bold_on%AwardWallet%bold_off% and claim ownership of your loyalty accounts (if any) and your travel plans (if any).%br%After registration, you will be the owner of your loyalty accounts on %bold_on%AwardWallet%bold_off% and you will be connected to %bold_on%%name%%bold_off% so that they can still see all of your accounts.")*/ 'invite.to.register'),
            Translator.trans(/** @Desc("%bold_on%%name%%bold_off% wants to connect with you on %bold_on%AwardWallet%bold_off%.%br%Please accept or reject this connection.")*/ 'invite.to.connect'),
            Translator.trans(/** @Desc("Yes, register, and establish connection")*/ 'invitation.accept'),
            Translator.trans(/** @Desc("Connect with %name%")*/ 'connect.with.person'),
            Translator.trans(/** @Desc("Reject this connection")*/ 'connection.reject'),
            Translator.trans(/** @Desc("Hello, %bold_on%%name%%bold_off%")*/ 'greeting'),
        ];
}]);
