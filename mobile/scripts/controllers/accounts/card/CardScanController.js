angular.module('AwardWalletMobile').controller('CardScanController', [
    '$q',
    '$scope',
    '$state',
    'Card',
    'CardStorage',
    function ($q, $scope, $state, Card, CardStorage) {
        var views = {
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
            back: {
                fileNamePrefix: 'Back',
                views: {
                    camera: {
                        title: Translator.trans(/** @Desc("Back of the Card") */'camera.back.title', {}, 'mobile'),
                        tooltip: Translator.trans(/** @Desc("Please take picture of the back of the card") */'camera.back.tooltip', {}, 'mobile'),
                        back: null,
                        next: Translator.trans(/** @Desc("Skip") */'skip', {}, 'mobile')
                    },
                    editor: {
                        title: null,
                        tooltip: Translator.trans(/** @Desc("Move and Scale") */'camera.edit.tooltip', {}, 'mobile'),
                        back: Translator.trans(/** @Desc("Retake") */'retake', {}, 'mobile'),
                        next: Translator.trans('form.button.save', {}, 'messages')
                    }
                }
            }
        };


        return {
            scan: function () {
                var UUID = window.UUID(),
                    images = {Front: {}, Back: {}},
                    card = new Card({
                        accountId: null,
                        images: images,
                        UUID: UUID
                    }),
                    promises = [],
                    barcode,
                    response;

                card.capture(views, function onCapture(image) {
                    var q = $q.defer();

                    if (image.barcode)
                        barcode = image.barcode;

                    image.UUID = UUID;

                    if (promises.length === 0)
                        $state.go('index.accounts.account-add', {scanData: {}}, {skipHistory: true});

                    promises.push(q.promise);

                    card.upload(image, function onUpload(data) {
                        console.log('image upload, finish', data);
                        if (!response || (!response && data.Kind === 'custom'))
                            response = data;

                        q.resolve({
                            side: image.side,
                            response: data
                        });
                    });

                }, function onClose() {
                    if (promises.length > 0) {
                        $state.go('index.accounts.account-add', {
                            scanData: {
                                card: card,
                                promises: promises,
                                barcode: barcode
                            }
                        })
                    }
                    console.log('closed', card, promises, response, barcode);
                });

            }
        }
    }
]);