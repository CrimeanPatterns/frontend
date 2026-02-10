self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }
    var json = event.data.json();
    var options = json.message;
    console.log('push received');
    console.log(json.title);
    console.log(options);
    var promise = self.registration.showNotification(json.title, options);
    event.waitUntil(promise);
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    if (event.notification.data && event.notification.data.url) {
        var promise = clients.openWindow(event.notification.data.url);
        event.waitUntil(promise);
    }
});

// Do we want to track closed notifications ?
// self.addEventListener('notificationclose', function(event) {
//   const dismissedNotification = event.notification;
//
//   const promiseChain = notificationCloseAnalytics();
//   event.waitUntil(promiseChain);
// });