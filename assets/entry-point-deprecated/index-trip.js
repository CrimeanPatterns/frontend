import webPush from 'common/web-push';

webPush.init(
    window.indexTripVapidPublicKey,
    window.indexTripWebpushId,
    window.indexTripUserId,
    window.indexTripIsUserStuff,
);
