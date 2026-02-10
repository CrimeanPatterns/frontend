angular.module('AwardWalletMobile').service('Centrifuge', [
  '$rootScope',
  function ($rootScope) {
    let centrifuge;
    let clientInfo;

    const _this = {
      configure(config) {
        if (!centrifuge) {
          console.log('configure centrifuge');
          clientInfo = config;
          centrifuge = new Centrifuge(config);
        }
      },
      connect() {
        if (centrifuge && !centrifuge.isConnected()) {
          centrifuge.connect();
        }
      },
      disconnect() {
        if (centrifuge && centrifuge.isConnected()) {
          centrifuge.disconnect();
        }
      },
      getConnection() {
        return centrifuge;
      },
      getClientInfo() {
        return clientInfo;
      },
      destroy() {
        _this.disconnect();
        centrifuge = null;
      }
    };

    $rootScope.$on('app:storage:destroy', _this.destroy);

    return _this;
  }
]);
