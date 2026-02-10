angular.module('AwardWalletMobile').controller('SubscriptionController', [
    '$scope',
    '$stateParams',
    function ($scope, $stateParams) {
        if($stateParams.action === 'cancel')
        {
            var content = {
                ios: Translator.trans(/** @Desc("%start_list% %start_list_item%Launch the Settings app on your iPhone or iPad.%end_list_item% %start_list_item%Tap on iTunes & App Store.%end_list_item% %start_list_item%Tap on your Apple ID at the top.%end_list_item% %start_list_item%Choose View Apple ID from the popup menu.%end_list_item% %start_list_item%Enter your password if/when prompted and tap on OK.%end_list_item% %start_list_item%Tap on Manage under Subscriptions.%end_list_item% %start_list_item%Tap on the AwardWallet subscription.%end_list_item% %start_list_item%Turn the Auto-Renewal option to Off.%end_list_item% %end_list%") */ 'ios.subscription.cancel', {
                    start_list: '<ol class="number-list">',
                    end_list: '</ol>',
                    start_list_item: '<li>',
                    end_list_item: '</li>'
                }, 'mobile'),
                android: Translator.trans(/** @Desc("%start_list% %start_list_item%Launch the Google Play Store app.%end_list_item% %start_list_item%Tap Menu -> My Apps -> Subscriptions and tap on AwardWallet.%end_list_item% %start_list_item%Alternatively, tap Menu -> My Apps -> Tap AwardWallet -> Tap the app details page.%end_list_item% %start_list_item%Tap Cancel and Yes to confirm the cancellation.%end_list_item% %start_list_item%Now, the status of this Subscription has been changed from Subscribed to Canceled.%end_list_item% %end_list%") */ 'android.subscription.cancel', {
                    start_list: '<ol class="number-list">',
                    end_list: '</ol>',
                    start_list_item: '<li>',
                    end_list_item: '</li>'
                }, 'mobile')
            };
            $scope.title = Translator.trans('subscription.cancel', {}, 'messages');
            $scope.text = content[$stateParams.platform] || content['ios'];
        }
        if($stateParams.action === 'info')
        {

            $scope.text = $scope.user.products[0].description;
        }

    }
]);