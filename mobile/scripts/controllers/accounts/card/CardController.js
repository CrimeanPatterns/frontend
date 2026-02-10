angular.module('AwardWalletMobile').controller('CardController', [
    '$scope',
    '$state',
    '$stateParams',
    'CardStorage',
    function ($scope, $state, $stateParams, CardStorage) {
        var translations = {
                camera: Translator.trans(/** @Desc("so that you can scan your physical loyalty cards and save them in AwardWallet") */'camera.usage', {}, 'mobile'),
                delete: Translator.trans(/** @Desc("Please confirm you want to delete this picture") */'confirm.delete.picture', {}, 'mobile')
            },
            views = {
                Front: {
                    front: {
                        fileNamePrefix: 'Front',
                        views: {
                            camera: {
                                title: Translator.trans(/** @Desc("Front of the Card") */'camera.front.title', {}, 'mobile'),
                                tooltip: Translator.trans(/** @Desc("Please take picture of the front of the card") */'camera.front.tooltip', {}, 'mobile'),
                                back: '',
                                next: null
                            },
                            editor: {
                                title: null,
                                tooltip: Translator.trans(/** @Desc("Move and Scale") */'camera.edit.tooltip', {}, 'mobile'),
                                back: Translator.trans(/** @Desc("Retake") */'retake', {}, 'mobile'),
                                next: Translator.trans('form.button.save', {}, 'messages')
                            }
                        }
                    },
                    back: null
                },
                Back: {
                    front: null,
                    back: {
                        fileNamePrefix: 'Back',
                        views: {
                            camera: {
                                title: Translator.trans(/** @Desc("Back of the Card") */'camera.back.title', {}, 'mobile'),
                                tooltip: Translator.trans(/** @Desc("Please take picture of the back of the card") */'camera.back.tooltip', {}, 'mobile'),
                                back: '',
                                next: null
                            },
                            editor: {
                                title: null,
                                tooltip: Translator.trans(/** @Desc("Move and Scale") */'camera.edit.tooltip', {}, 'mobile'),
                                back: Translator.trans(/** @Desc("Retake") */'retake', {}, 'mobile'),
                                next: Translator.trans('form.button.save', {}, 'messages')
                            }
                        }
                    }
                }
            };

        var field = $scope.block || $scope.field,
            remove = true;

        if ($scope.account && $scope.account.Access) {
            remove = $scope.account.Access.delete;
        }

        var card = field.card || CardStorage.get({
            accountId: $stateParams.Id,
            subAccountId: $stateParams.subId,
            images: field.Val || field.value
        });

        $scope.getCardStyle = function (url) {
            return url ? {
                'background-image': 'url(' + url + ')',
                'background-size': 'cover'
            } : {};
        };

        $scope.processCard = function (side) {
            if (platform.cordova) {
                if (card.images[side] && card.images[side].localPath) {
                    PhotoViewer.open({filePath: card.images[side].localPath, remove: remove}, function (response) {
                        $scope.$applyAsync(function () {
                            if (
                                response &&
                                response.action === 'remove'
                            ) {
                                if (field.value && field.value[side]) {
                                    field.value[side].CardImageId = null;
                                    field.value[side].FileName = null;
                                    field.value[side].Url = null;
                                }
                                card.remove(side);
                            }
                        });
                    }, function () {
                    });
                } else {
                    card.capture(views[side], function (response) {
                        card.upload(response, function (response) {
                            if (field.value && field.value[response.Kind]) {
                                field.value[response.Kind].CardImageId = response.CardImageId;
                                field.value[response.Kind].FileName = response.FileName;
                            }
                        });
                    });
                }
            }
        };

        return card;
    }
]);