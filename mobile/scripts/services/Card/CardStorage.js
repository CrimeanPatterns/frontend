angular.module('AwardWalletMobile').service('CardStorage', [
    'DatabaseStorage',
    'Card',
    function (DatabaseStorage, Card) {
        var storageData = DatabaseStorage.get('cards');
        if (!storageData) {
            storageData = DatabaseStorage.put('cards', {});
        }

        function CardStorage(storage) {
            this.storage = {};
            for (var key in storage) {
                if (storage.hasOwnProperty(key)) {
                    this.storage[key] = new Card(storage[key]);
                }
            }
            return this;
        }

        function getStorageKey(accountId, subAccountId) {
            var key = accountId;
            if (subAccountId)
                key += '.' + subAccountId;
            return key;
        }

        return angular.extend(new CardStorage(storageData), {
            add: function (card) {
                if (card instanceof Card) {
                    var key = getStorageKey(card.accountId, card.subAccountId);
                    this.storage[key] = card;
                    return this.storage[key];
                }
            },
            remove: function (card) {
                if (card instanceof Card) {
                    var key = getStorageKey(card.accountId, card.subAccountId);
                    card.cleanup();
                    delete this.storage[key];
                }
            },
            get: function (props) {
                var data = props, key = getStorageKey(props.accountId, props.subAccountId);
                if (
                    this.storage.hasOwnProperty(key) &&
                    platform.cordova
                ) {
                    data = this.storage[key];
                    if (props.images) {
                        for (var side in props.images) {
                            if (props.images.hasOwnProperty(side)) {
                                if (
                                    data.images[side] &&
                                    [
                                        data.states.LOADING,
                                        data.states.REJECTED,
                                        data.states.REMOVED
                                    ].indexOf(data.images[side].state) === -1 &&
                                    props.images[side].FileName
                                ) {
                                    angular.extend(data.images[side], props.images[side]);
                                } else {
                                    data.images[side] = props.images[side];
                                }
                            }
                        }
                    }
                }
                return this.add(new Card(data));
            },
            save: function () {
                storageData = DatabaseStorage.put('cards', this.storage);
            },
            cleanup: function () {
                for (var key in this.storage) {
                    if (this.storage.hasOwnProperty(key)) {
                        this.storage[key].cleanup();
                    }
                }
                this.storage = {};
                storageData = DatabaseStorage.put('cards', {});
                navigator.camera.cleanup();
            }
        });
    }
]);